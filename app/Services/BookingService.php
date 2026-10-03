<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\RateUnit;
use App\Models\ActivityLog;
use App\Models\AvailabilityBlock;
use App\Models\Booking;
use App\Models\Listing;
use App\Models\ListingRate;
use App\Models\SiteVisit;
use App\Models\User;
use App\Notifications\BookingRequested;
use App\Notifications\BookingStatusChanged;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use LogicException;

/**
 * Creates bookings without overbooking and moves them through their states.
 *
 * Each date a listing is booked on has an availability row. Requests lock those rows inside a
 * transaction, check the remaining slots and reserve them, so two tourists cannot take the last
 * slot at once. Declined, cancelled and expired bookings give their slots back.
 */
class BookingService
{
    public function __construct(private ItineraryPlanner $planner) {}

    /**
     * How many units (seats, vehicles, rooms, items) a booking uses, and what it costs.
     *
     * @return array{quantity: int, total: float}
     */
    public function quote(ListingRate $rate, int $pax, int $nights = 1): array
    {
        $price = (float) $rate->price;
        $groups = $rate->capacity ? (int) ceil($pax / $rate->capacity) : 1;

        [$quantity, $total] = match ($rate->unit) {
            RateUnit::PerPerson => [$pax, $price * $pax],
            RateUnit::PerItem => [$pax, $price * $pax],
            RateUnit::PerGroup, RateUnit::PerRide => [$groups, $price * $groups],
            RateUnit::PerNight => [$groups, $price * $groups * max(1, $nights)],
            RateUnit::PerHour => [1, $price],
        };

        return ['quantity' => $quantity, 'total' => round($total, 2)];
    }

    /**
     * Slots still free on a date, or null when the listing has no limit that day.
     */
    public function remainingSlots(Listing $listing, CarbonInterface $date): ?int
    {
        $block = $listing->availability()->whereDate('date', $date->toDateString())->first();

        if ($block?->is_closed) {
            return 0;
        }

        $total = $block?->slots_total ?? $listing->default_daily_slots;

        return $total === null ? null : max(0, $total - ($block->slots_booked ?? 0));
    }

    /**
     * @param  array{date: string, time?: string|null, nights?: int|null, pax: int, trip_id?: int|null, tourist_note?: string|null}  $data
     */
    public function request(User $tourist, Listing $listing, ListingRate $rate, array $data): Booking
    {
        if (! $listing->is_bookable || ! $listing->isPublished() || $rate->listing_id !== $listing->id || ! $rate->is_active) {
            throw ValidationException::withMessages(['listing_rate_id' => __('This rate cannot be booked.')]);
        }

        $quote = $this->quote($rate, (int) $data['pax'], (int) ($data['nights'] ?? 1));

        $booking = DB::transaction(function () use ($tourist, $listing, $rate, $data, $quote) {
            $booking = new Booking([
                ...$data,
                'listing_rate_id' => $rate->id,
                'nights' => $rate->unit === RateUnit::PerNight ? max(1, (int) ($data['nights'] ?? 1)) : 1,
            ]);
            $booking->forceFill([
                'user_id' => $tourist->id,
                'listing_id' => $listing->id,
                'rate_name' => $rate->name,
                'unit_price' => $rate->price,
                'unit' => $rate->unit,
                'quantity' => $quote['quantity'],
                'total_amount' => $quote['total'],
                'status' => BookingStatus::Pending,
                'expires_at' => now()->addHours(Booking::RESPONSE_HOURS),
            ]);

            $this->reserve($listing, $booking->dates(), $quote['quantity']);

            $booking->save();

            return $booking;
        });

        $listing->loadMissing('business.owner');
        $listing->business?->owner?->notify(new BookingRequested($booking));

        return $booking;
    }

    public function confirm(Booking $booking, User $partner): void
    {
        $this->transition($booking, BookingStatus::Confirmed, $partner, function (Booking $booking) {
            $booking->confirmed_at = now();
            $booking->qr_token = Str::random(40);
        });

        $this->addToItinerary($booking);
    }

    public function decline(Booking $booking, User $partner, string $reason): void
    {
        $this->transition($booking, BookingStatus::Declined, $partner, function (Booking $booking) use ($reason) {
            $booking->declined_at = now();
            $booking->partner_note = $reason;
        });
    }

    public function cancel(Booking $booking, User $by, ?string $reason = null): void
    {
        if (! $booking->isCancellable()) {
            throw ValidationException::withMessages(['booking' => __('This booking can no longer be cancelled.')]);
        }

        $this->transition($booking, BookingStatus::Cancelled, $by, function (Booking $booking) use ($by, $reason) {
            $booking->cancelled_at = now();
            $booking->cancelled_by = $by->id;
            $booking->partner_note = $reason ?? $booking->partner_note;
        });

        $booking->itineraryItem?->delete();
    }

    /**
     * The partner scans the voucher's QR code (or types its code) when the tourist arrives.
     */
    public function checkIn(Booking $booking, User $partner): void
    {
        if ($booking->status !== BookingStatus::Confirmed) {
            throw ValidationException::withMessages(['code' => __('Booking :code is :status, not confirmed.', [
                'code' => $booking->code,
                'status' => Str::lower(__($booking->status->label())),
            ])]);
        }

        $this->transition($booking, BookingStatus::Completed, $partner, function (Booking $booking) {
            $booking->checked_in_at = now();
        });

        SiteVisit::record($booking->listing_id, $booking->user_id, now()->toDateString(), 'booking');
    }

    public function expire(Booking $booking): void
    {
        $this->transition($booking, BookingStatus::Expired, null);
    }

    public function markNoShow(Booking $booking): void
    {
        $this->transition($booking, BookingStatus::NoShow, null);
    }

    /**
     * @param  (callable(Booking): void)|null  $apply
     */
    private function transition(Booking $booking, BookingStatus $next, ?User $actor, ?callable $apply = null): void
    {
        $previous = $booking->status;

        DB::transaction(function () use ($booking, $next, $apply, $previous) {
            $booking->refresh();

            if (! $booking->status->canMoveTo($next)) {
                throw new LogicException("A {$booking->status->value} booking cannot become {$next->value}.");
            }

            $booking->status = $next;

            if ($apply !== null) {
                $apply($booking);
            }

            $booking->save();

            if ($previous->holdsSlot() && ! $next->holdsSlot()) {
                $this->release($booking);
            }
        });

        ActivityLog::create([
            'user_id' => $actor?->id,
            'action' => "booking.{$next->value}",
            'subject_type' => $booking->getMorphClass(),
            'subject_id' => $booking->id,
            'properties' => ['from' => $previous->value],
            'ip_address' => request()->ip(),
        ]);

        $this->notifyChange($booking, $actor);
    }

    /**
     * @param  list<CarbonImmutable>  $dates
     */
    private function reserve(Listing $listing, array $dates, int $quantity): void
    {
        foreach ($dates as $date) {
            AvailabilityBlock::forDate($listing->id, $date);

            $block = AvailabilityBlock::where('listing_id', $listing->id)
                ->whereDate('date', $date->toDateString())
                ->lockForUpdate()
                ->first();

            $total = $block->slots_total ?? $listing->default_daily_slots;

            if ($block->is_closed || ($total !== null && $block->slots_booked + $quantity > $total)) {
                throw ValidationException::withMessages(['date' => $block->is_closed
                    ? __(':name is closed on :date.', ['name' => $listing->name, 'date' => $date->format('F j')])
                    : __('Not enough slots left on :date. Only :count available.', ['date' => $date->format('F j'), 'count' => max(0, $total - $block->slots_booked)])]);
            }

            $block->increment('slots_booked', $quantity);
        }
    }

    private function release(Booking $booking): void
    {
        foreach ($booking->dates() as $date) {
            AvailabilityBlock::where('listing_id', $booking->listing_id)
                ->whereDate('date', $date->toDateString())
                ->where('slots_booked', '>=', $booking->quantity)
                ->decrement('slots_booked', $booking->quantity);
        }
    }

    /**
     * A confirmed booking appears on the tourist's trip, pinned to its time.
     */
    private function addToItinerary(Booking $booking): void
    {
        $trip = $booking->trip;

        if ($trip === null || $booking->date->lt($trip->start_date) || $booking->date->gt($trip->end_date)) {
            return;
        }

        $day = (int) $trip->start_date->diffInDays($booking->date) + 1;

        $trip->items()->create([
            'listing_id' => $booking->listing_id,
            'booking_id' => $booking->id,
            'day_number' => $day,
            'position' => (int) $trip->items()->where('day_number', $day)->max('position') + 1,
            'fixed_start_time' => $booking->time ? substr($booking->time, 0, 5) : null,
            'notes' => __('Booked: :rate (:code)', ['rate' => $booking->rate_name, 'code' => $booking->code]),
        ]);

        $this->planner->schedule($trip);
    }

    private function notifyChange(Booking $booking, ?User $actor): void
    {
        $booking->loadMissing(['tourist', 'listing.business.owner']);
        $partner = $booking->listing->business?->owner;

        // Tell whoever did not make the change.
        if ($actor === null || $actor->id !== $booking->user_id) {
            $booking->tourist->notify(new BookingStatusChanged($booking, forPartner: false));
        }

        if ($partner !== null && $actor?->id === $booking->user_id) {
            $partner->notify(new BookingStatusChanged($booking, forPartner: true));
        }
    }
}

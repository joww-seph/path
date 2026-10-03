<?php

namespace App\Models;

use App\Enums\BookingStatus;
use App\Enums\RateUnit;
use Carbon\CarbonImmutable;
use Database\Factories\BookingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

/**
 * A tourist's booking request for a partner listing.
 *
 * The rate name, unit and price are copied when the booking is made, so later price changes do
 * not alter it. "quantity" is how many units it uses (rooms, vehicles, items) on each date.
 *
 * @property int $id
 * @property string $code
 * @property int $user_id
 * @property int|null $trip_id
 * @property int $listing_id
 * @property int|null $listing_rate_id
 * @property string $rate_name
 * @property string $unit_price
 * @property RateUnit $unit
 * @property CarbonImmutable $date
 * @property string|null $time
 * @property int $nights
 * @property int $pax
 * @property int $quantity
 * @property string $total_amount
 * @property BookingStatus $status
 * @property string|null $tourist_note
 * @property string|null $partner_note
 * @property string|null $qr_token
 * @property CarbonImmutable|null $expires_at
 * @property CarbonImmutable|null $confirmed_at
 * @property CarbonImmutable|null $checked_in_at
 * @property CarbonImmutable|null $declined_at
 * @property CarbonImmutable|null $cancelled_at
 * @property int|null $cancelled_by
 * @property-read User $tourist
 * @property-read Listing $listing
 * @property-read Trip|null $trip
 */
#[Fillable(['trip_id', 'listing_rate_id', 'date', 'time', 'nights', 'pax', 'tourist_note'])]
class Booking extends Model
{
    /** @use HasFactory<BookingFactory> */
    use HasFactory;

    /**
     * Partners have this long to answer a request before it expires.
     */
    public const RESPONSE_HOURS = 48;

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'pending',
        'nights' => 1,
        'quantity' => 1,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'unit' => RateUnit::class,
            'unit_price' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'date' => 'immutable_date',
            'nights' => 'integer',
            'pax' => 'integer',
            'quantity' => 'integer',
            'status' => BookingStatus::class,
            'expires_at' => 'immutable_datetime',
            'confirmed_at' => 'immutable_datetime',
            'checked_in_at' => 'immutable_datetime',
            'declined_at' => 'immutable_datetime',
            'cancelled_at' => 'immutable_datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Booking $booking) {
            $booking->code ??= self::newCode();
        });
    }

    /**
     * A short code that is easy to read out over the phone: no 0/O or 1/I.
     */
    public static function newCode(): string
    {
        do {
            $code = 'PTH-'.collect(range(1, 6))->map(fn () => Str::of('ABCDEFGHJKLMNPQRSTUVWXYZ23456789')->charAt(random_int(0, 31)))->implode('');
        } while (self::where('code', $code)->exists());

        return $code;
    }

    public function getRouteKeyName(): string
    {
        return 'code';
    }

    /**
     * The dates this booking uses: one for most bookings, one per night for stays.
     *
     * @return list<CarbonImmutable>
     */
    public function dates(): array
    {
        $nights = $this->unit === RateUnit::PerNight ? max(1, $this->nights) : 1;

        return array_map(fn (int $offset) => $this->date->addDays($offset), range(0, $nights - 1));
    }

    /**
     * Confirmed bookings can be cancelled until their date; pending ones any time.
     */
    public function isCancellable(): bool
    {
        return match ($this->status) {
            BookingStatus::Pending => true,
            BookingStatus::Confirmed => $this->date->endOfDay()->isFuture(),
            default => false,
        };
    }

    /**
     * @param  Builder<Booking>  $query
     */
    public function scopeForPartner(Builder $query, User $partner): void
    {
        $query->whereHas('listing.business', fn (Builder $query) => $query->where('owner_id', $partner->id));
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function tourist(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return BelongsTo<Listing, $this>
     */
    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }

    /**
     * @return BelongsTo<ListingRate, $this>
     */
    public function rate(): BelongsTo
    {
        return $this->belongsTo(ListingRate::class, 'listing_rate_id');
    }

    /**
     * @return BelongsTo<Trip, $this>
     */
    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    /**
     * The itinerary stop created when the booking was confirmed.
     *
     * @return HasOne<ItineraryItem, $this>
     */
    public function itineraryItem(): HasOne
    {
        return $this->hasOne(ItineraryItem::class);
    }
}

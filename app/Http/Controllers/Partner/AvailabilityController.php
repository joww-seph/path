<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use App\Models\AvailabilityBlock;
use App\Models\Listing;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Daily capacity for a bookable listing: a default number of slots, and per-date changes or closures.
 */
class AvailabilityController extends Controller
{
    public function show(Request $request, Listing $listing): Response
    {
        Gate::authorize('update', $listing);

        $validated = $request->validate(['month' => ['nullable', 'date_format:Y-m']]);
        $month = isset($validated['month'])
            ? CarbonImmutable::createFromFormat('Y-m', $validated['month'])->startOfMonth()
            : now()->startOfMonth();

        return Inertia::render('partner/listings/Availability', [
            'listing' => $listing->only(['id', 'name', 'slug', 'default_daily_slots', 'is_bookable']),
            'month' => $month->format('Y-m'),
            'blocks' => $listing->availability()
                ->whereDate('date', '>=', $month->toDateString())->whereDate('date', '<=', $month->endOfMonth()->toDateString())
                ->get()
                ->mapWithKeys(fn (AvailabilityBlock $block) => [$block->date->toDateString() => [
                    'slots_total' => $block->slots_total,
                    'slots_booked' => $block->slots_booked,
                    'is_closed' => $block->is_closed,
                ]]),
        ]);
    }

    public function updateDefault(Request $request, Listing $listing): RedirectResponse
    {
        Gate::authorize('update', $listing);

        $validated = $request->validate(['default_daily_slots' => ['nullable', 'integer', 'min:0', 'max:1000']]);

        $listing->forceFill(['default_daily_slots' => $validated['default_daily_slots']])->save();

        return back();
    }

    /**
     * Set the slots or closure for one or more dates.
     */
    public function update(Request $request, Listing $listing): RedirectResponse
    {
        Gate::authorize('update', $listing);

        $validated = $request->validate([
            'dates' => ['required', 'array', 'min:1', 'max:62'],
            'dates.*' => ['date_format:Y-m-d', 'after_or_equal:today'],
            'slots_total' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'is_closed' => ['boolean'],
        ]);

        foreach ($validated['dates'] as $date) {
            $block = AvailabilityBlock::forDate($listing->id, $date);

            if (isset($validated['slots_total']) && $validated['slots_total'] < $block->slots_booked) {
                throw ValidationException::withMessages(['slots_total' => __(':date already has :count slots booked. Cancel bookings before lowering the limit.', ['date' => CarbonImmutable::parse($date)->format('F j'), 'count' => $block->slots_booked])]);
            }

            $block->slots_total = $validated['slots_total'] ?? null;
            $block->is_closed = $request->boolean('is_closed');
            $block->save();
        }

        return back();
    }
}

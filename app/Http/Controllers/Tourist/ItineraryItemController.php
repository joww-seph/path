<?php

namespace App\Http\Controllers\Tourist;

use App\Http\Controllers\Controller;
use App\Http\Requests\Trips\ItineraryItemRequest;
use App\Models\ItineraryItem;
use App\Models\Trip;
use App\Services\ItineraryPlanner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class ItineraryItemController extends Controller
{
    public function __construct(private ItineraryPlanner $planner) {}

    /**
     * Add a listing or a custom stop to the end of a day.
     */
    public function store(ItineraryItemRequest $request, Trip $trip): RedirectResponse
    {
        Gate::authorize('update', $trip);

        $validated = $request->validated();

        if (isset($validated['client_uuid']) && $trip->items()->where('client_uuid', $validated['client_uuid'])->exists()) {
            return back();
        }

        $trip->items()->create([
            ...$validated,
            'position' => (int) $trip->items()->where('day_number', $validated['day_number'])->max('position') + 1,
        ]);

        $this->planner->schedule($trip);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Added to day :day of :trip.', ['day' => $validated['day_number'], 'trip' => $trip->title])]);

        return back();
    }

    public function update(ItineraryItemRequest $request, Trip $trip, ItineraryItem $item): RedirectResponse
    {
        Gate::authorize('update', $trip);

        $validated = $request->validated();

        if (isset($validated['day_number']) && $validated['day_number'] !== $item->day_number) {
            $validated['position'] = (int) $trip->items()->where('day_number', $validated['day_number'])->max('position') + 1;
        }

        $item->update($validated);

        $this->planner->schedule($trip);

        return back();
    }

    public function destroy(Trip $trip, ItineraryItem $item): RedirectResponse
    {
        Gate::authorize('update', $trip);

        $item->delete();

        $this->planner->schedule($trip);

        return back();
    }

    /**
     * Save a new order after drag and drop: a list of item ids for each day.
     */
    public function reorder(Request $request, Trip $trip): RedirectResponse
    {
        Gate::authorize('update', $trip);

        $validated = $request->validate([
            'days' => ['required', 'array'],
            'days.*' => ['array'],
            'days.*.*' => ['integer'],
        ]);

        $ids = collect($validated['days'])->flatten()->map(fn ($id) => (int) $id);
        $tripItemIds = $trip->items()->pluck('id');

        if ($ids->diff($tripItemIds)->isNotEmpty() || $ids->duplicates()->isNotEmpty()) {
            throw ValidationException::withMessages(['days' => __('The itinerary changed. Reload the page and try again.')]);
        }

        DB::transaction(function () use ($validated, $trip) {
            foreach ($validated['days'] as $day => $itemIds) {
                if ((int) $day < 1 || (int) $day > $trip->dayCount()) {
                    continue;
                }

                foreach (array_values($itemIds) as $position => $id) {
                    $trip->items()->whereKey($id)->update(['day_number' => (int) $day, 'position' => $position]);
                }
            }
        });

        $this->planner->schedule($trip);

        return back();
    }
}

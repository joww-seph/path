<?php

namespace App\Http\Controllers\Tourist;

use App\Actions\Trips\BuildPlannerView;
use App\Enums\TravelMode;
use App\Http\Controllers\Controller;
use App\Http\Requests\Trips\TripRequest;
use App\Http\Resources\ListingCardResource;
use App\Http\Resources\TripResource;
use App\Models\ItineraryTemplate;
use App\Models\Trip;
use App\Services\ItineraryPlanner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class TripController extends Controller
{
    public function index(Request $request): Response
    {
        $trips = Trip::accessibleBy($request->user())
            ->with('owner')
            ->withCount('items')
            ->orderByRaw('case when end_date >= ? then 0 else 1 end', [now()->toDateString()])
            ->orderBy('start_date')
            ->get();

        return Inertia::render('tourist/trips/Index', [
            'trips' => TripResource::collection($trips)->resolve(),
            'templates' => $this->templates(),
            'travelModes' => TravelMode::options(),
        ]);
    }

    /**
     * Create a trip, optionally filled from a ready-made template.
     */
    public function store(TripRequest $request, ItineraryPlanner $planner): RedirectResponse
    {
        $trip = DB::transaction(function () use ($request) {
            $trip = $request->user()->trips()->create($request->tripAttributes());

            if ($slug = $request->validated('template')) {
                $template = ItineraryTemplate::where('slug', $slug)->with('items.listing')->firstOrFail();

                foreach ($template->items as $templateItem) {
                    if ($templateItem->listing_id !== null && ! $templateItem->listing?->isPublished()) {
                        continue;
                    }

                    $trip->items()->create([
                        'listing_id' => $templateItem->listing_id,
                        'custom_title' => $templateItem->custom_title,
                        'day_number' => min($templateItem->day_number, $trip->dayCount()),
                        'position' => $templateItem->position,
                        'duration_minutes' => $templateItem->duration_minutes,
                        'notes' => $templateItem->notes,
                    ]);
                }
            }

            return $trip;
        });

        $planner->schedule($trip);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Trip created. Add places from Explore or the suggestions.')]);

        return to_route('tourist.trips.show', $trip);
    }

    public function show(Request $request, Trip $trip, BuildPlannerView $view, ItineraryPlanner $planner): Response
    {
        Gate::authorize('view', $trip);

        return Inertia::render('tourist/trips/Show', [
            ...$view->handle($trip, $request),
            'members' => $trip->members->map(fn ($member) => [
                'id' => $member->id,
                'name' => $member->name,
                'email' => $member->email,
                'role' => $member->pivot->role->value,
            ]),
            'suggestions' => ListingCardResource::collection($planner->suggestions($trip, $request->user()))->resolve(),
            'travelModes' => TravelMode::options(),
            'can' => [
                'update' => Gate::allows('update', $trip),
                'manage' => Gate::allows('manage', $trip),
            ],
        ]);
    }

    /**
     * Update trip details. Stops on days the trip no longer covers move to its last day.
     */
    public function update(TripRequest $request, Trip $trip, ItineraryPlanner $planner): RedirectResponse
    {
        Gate::authorize('update', $trip);

        $trip->update($request->tripAttributes());
        $trip->items()->where('day_number', '>', $trip->dayCount())->update(['day_number' => $trip->dayCount()]);

        $planner->schedule($trip);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Trip details saved.')]);

        return back();
    }

    public function destroy(Trip $trip): RedirectResponse
    {
        Gate::authorize('manage', $trip);

        $trip->delete();

        return to_route('tourist.trips.index');
    }

    /**
     * Rebuild the plan: reorder one day, or spread every stop across all days.
     */
    public function arrange(Request $request, Trip $trip, ItineraryPlanner $planner): RedirectResponse
    {
        Gate::authorize('update', $trip);

        $validated = $request->validate([
            'mode' => ['required', 'in:day,all'],
            'day' => ['required_if:mode,day', 'integer', 'min:1', 'max:'.$trip->dayCount()],
        ]);

        if ($validated['mode'] === 'day') {
            $planner->arrange($trip, (int) $validated['day']);
        } else {
            $planner->balance($trip);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Stops rearranged to cut travel time.')]);

        return back();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function templates()
    {
        return ItineraryTemplate::where('is_published', true)
            ->withCount('items')
            ->orderBy('days')
            ->get(['id', 'name', 'slug', 'summary', 'days']);
    }
}

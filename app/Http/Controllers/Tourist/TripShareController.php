<?php

namespace App\Http\Controllers\Tourist;

use App\Http\Controllers\Controller;
use App\Models\Trip;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

/**
 * A read-only link anyone can open to see the itinerary.
 */
class TripShareController extends Controller
{
    public function store(Trip $trip): RedirectResponse
    {
        Gate::authorize('manage', $trip);

        $trip->enableSharing();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Share link created. Anyone with the link can view this itinerary.')]);

        return back();
    }

    public function destroy(Trip $trip): RedirectResponse
    {
        Gate::authorize('manage', $trip);

        $trip->forceFill(['share_token' => null])->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Share link turned off.')]);

        return back();
    }
}

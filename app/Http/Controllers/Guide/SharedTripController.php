<?php

namespace App\Http\Controllers\Guide;

use App\Actions\Trips\BuildPlannerView;
use App\Http\Controllers\Controller;
use App\Models\Trip;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SharedTripController extends Controller
{
    /**
     * A read-only itinerary opened from a share link.
     */
    public function __invoke(Request $request, string $token, BuildPlannerView $view): Response
    {
        $trip = Trip::where('share_token', $token)->firstOrFail();

        $data = $view->handle($trip, $request);
        unset($data['trip']['share_url']);

        return Inertia::render('guide/SharedTrip', $data);
    }
}

<?php

namespace App\Http\Controllers\Tourist;

use App\Http\Controllers\Controller;
use App\Http\Resources\TripResource;
use App\Models\Trip;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();

        $nextTrip = Trip::accessibleBy($user)
            ->where('end_date', '>=', now()->toDateString())
            ->withCount('items')
            ->orderBy('start_date')
            ->first();

        return Inertia::render('tourist/Dashboard', [
            'nextTrip' => $nextTrip ? (new TripResource($nextTrip))->resolve() : null,
            'checklist' => [
                'preferences' => $user->touristProfile()->exists(),
                'emergencyContacts' => $user->emergencyContacts()->exists(),
                'phoneVerified' => $user->phone_verified_at !== null,
            ],
        ]);
    }
}

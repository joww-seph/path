<?php

namespace App\Http\Controllers\Api;

use App\Actions\Trips\BuildPlannerView;
use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Hotline;
use App\Models\Listing;
use App\Models\Trip;
use App\Services\BudgetService;
use App\Support\QrCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Everything a tourist needs on the ground without signal, saved to the phone in one download.
 */
class OfflineTripController extends Controller
{
    public function __invoke(Request $request, Trip $trip, BuildPlannerView $view, BudgetService $budget): JsonResponse
    {
        Gate::authorize('view', $trip);

        $planner = $view->handle($trip, $request);

        $listings = Listing::whereIn('id', $trip->items->pluck('listing_id')->filter())
            ->get(['id', 'name', 'slug', 'summary', 'address', 'latitude', 'longitude', 'opening_hours', 'entrance_fee', 'contact_phone'])
            ->keyBy('id');

        return response()->json([
            'saved_at' => now()->toIso8601String(),
            'trip' => $planner['trip'],
            'days' => $planner['days'],
            'warnings' => $planner['warnings'],
            'listings' => $listings,
            'budget' => $budget->summary($trip, $planner['estimatedCost']),
            'expenses' => $trip->expenses()->orderByDesc('spent_on')->get(['id', 'category', 'amount', 'spent_on', 'note']),
            'travellers' => $trip->travellers()->map->only(['id', 'name'])->values(),
            'hotlines' => Hotline::orderBy('position')->get(['name', 'type', 'phone', 'description']),
            'emergency_contacts' => $request->user()->emergencyContacts()->get(['name', 'relationship', 'phone']),
            'vouchers' => $request->user()->bookings()
                ->where('trip_id', $trip->id)
                ->where('status', BookingStatus::Confirmed)
                ->with('listing:id,name,address,contact_phone')
                ->get()
                ->map(fn (Booking $booking) => [
                    'code' => $booking->code,
                    'listing' => $booking->listing->name,
                    'address' => $booking->listing->address,
                    'contact_phone' => $booking->listing->contact_phone,
                    'rate_name' => $booking->rate_name,
                    'date' => $booking->date->toDateString(),
                    'time' => $booking->time ? substr($booking->time, 0, 5) : null,
                    'pax' => $booking->pax,
                    'total_amount' => $booking->total_amount,
                    'qr' => QrCode::svg(route('partner.check-in.show', $booking->qr_token)),
                ]),
            'can_update' => Gate::allows('update', $trip),
        ]);
    }
}

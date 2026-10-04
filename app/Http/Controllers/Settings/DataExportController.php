<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Business;
use App\Models\EmergencyContact;
use App\Models\Expense;
use App\Models\ItineraryItem;
use App\Models\Review;
use App\Models\SiteVisit;
use App\Models\SosAlert;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Lets a user download a copy of their personal data, as the Data Privacy Act (RA 10173) allows.
 */
class DataExportController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();

        $filename = 'path-my-data-'.now()->format('Y-m-d').'.json';

        return response()
            ->json($this->export($user), options: JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }

    /**
     * @return array<string, mixed>
     */
    private function export(User $user): array
    {
        $user->load(['touristProfile', 'emergencyContacts', 'businesses.listings:id,business_id,name,slug,status']);

        return [
            'exported_at' => now()->toIso8601String(),
            'account' => [
                ...$user->only(['name', 'email', 'phone', 'locale', 'created_at', 'email_verified_at', 'phone_verified_at', 'privacy_accepted_at']),
                'role' => $user->role->value,
                'signed_in_with_google' => $user->google_id !== null,
                'two_factor_enabled' => $user->two_factor_confirmed_at !== null,
            ],
            'travel_preferences' => $user->touristProfile?->only(['interests', 'group_size', 'budget_min', 'budget_max', 'accessibility_needs', 'home_province', 'home_country']),
            'emergency_contacts' => $user->emergencyContacts->map(fn (EmergencyContact $contact) => $contact->only(['name', 'relationship', 'phone', 'email'])),
            'trips' => Trip::accessibleBy($user)->with(['items.listing:id,name', 'members:id,name'])->orderBy('start_date')->get()
                ->map(fn (Trip $trip) => [
                    ...$trip->only(['title', 'start_date', 'end_date', 'pax', 'budget', 'travel_mode', 'created_at']),
                    'owned_by_me' => $trip->user_id === $user->id,
                    'members' => $trip->members->pluck('name'),
                    'stops' => $trip->items->map(fn (ItineraryItem $item) => [
                        'day' => $item->day_number,
                        'place' => $item->title(),
                        'start_time' => $item->start_time,
                        'notes' => $item->notes,
                        'done' => $item->is_done,
                    ]),
                ]),
            'expenses_i_paid' => Expense::where('paid_by', $user->id)->with('trip:id,title')->orderBy('spent_on')->get()
                ->map(fn (Expense $expense) => [
                    'trip' => $expense->trip?->title,
                    ...$expense->only(['category', 'amount', 'spent_on', 'note']),
                ]),
            'bookings' => Booking::where('user_id', $user->id)->with('listing:id,name')->latest('date')->get()
                ->map(fn (Booking $booking) => [
                    'listing' => $booking->listing?->name,
                    ...$booking->only(['code', 'rate_name', 'date', 'time', 'nights', 'pax', 'quantity', 'total_amount', 'tourist_note', 'partner_note', 'created_at', 'checked_in_at']),
                    'status' => $booking->status->value,
                ]),
            'reviews' => Review::where('user_id', $user->id)->with('listing:id,name')->get()
                ->map(fn (Review $review) => [
                    'listing' => $review->listing?->name,
                    ...$review->only(['rating', 'comment', 'created_at']),
                    'status' => $review->status->value,
                ]),
            'site_visits' => SiteVisit::where('user_id', $user->id)->with('listing:id,name')->orderBy('visited_on')->get()
                ->map(fn (SiteVisit $visit) => ['listing' => $visit->listing?->name, 'date' => $visit->visited_on]),
            'sos_alerts' => SosAlert::where('user_id', $user->id)->latest()->get()
                ->map(fn (SosAlert $alert) => [
                    ...$alert->only(['latitude', 'longitude', 'message', 'contacts_notified', 'created_at', 'resolved_at']),
                    'status' => $alert->status->value,
                ]),
            'businesses' => $user->businesses->map(fn (Business $business) => [
                ...$business->only(['name', 'permit_no', 'contact_phone', 'contact_email', 'address', 'created_at']),
                'listings' => $business->listings->pluck('name'),
            ]),
        ];
    }
}

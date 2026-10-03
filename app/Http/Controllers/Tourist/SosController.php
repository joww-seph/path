<?php

namespace App\Http\Controllers\Tourist;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\Hotline;
use App\Models\SosAlert;
use App\Models\Trip;
use App\Models\User;
use App\Notifications\SosRaised;
use App\Services\SmsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class SosController extends Controller
{
    public function show(Request $request): Response
    {
        return Inertia::render('tourist/Sos', [
            'contacts' => $request->user()->emergencyContacts()->get(['id', 'name', 'relationship', 'phone']),
            'hotlines' => Hotline::orderBy('position')->get(['id', 'name', 'type', 'phone', 'description']),
            'recentAlert' => SosAlert::where('user_id', $request->user()->id)->latest()->first(['id', 'status', 'contacts_notified', 'created_at']),
        ]);
    }

    /**
     * Raise an SOS: text and email the tourist's emergency contacts their location, and alert the office.
     * Location is only included when the tourist allowed the browser to share it.
     */
    public function store(Request $request, SmsService $sms): RedirectResponse
    {
        $validated = $request->validate([
            'latitude' => ['nullable', 'numeric', 'between:-90,90', 'required_with:longitude'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180', 'required_with:latitude'],
            'accuracy_meters' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'message' => ['nullable', 'string', 'max:500'],
        ]);

        $user = $request->user();

        $alert = new SosAlert($validated);
        $alert->user_id = $user->id;
        $alert->trip_id = Trip::accessibleBy($user)
            ->where('start_date', '<=', now()->toDateString().' 23:59:59')
            ->where('end_date', '>=', now()->toDateString())
            ->value('id');
        $alert->save();

        $text = __('SOS from :name via PaTH (Paoay). :location :message Call them, or 911 if you cannot reach them.', [
            'name' => $user->name,
            'location' => $alert->mapUrl() ? __('Location: :url', ['url' => $alert->mapUrl()]) : __('Location not shared.'),
            'message' => $alert->message ? '"'.$alert->message.'"' : '',
        ]);

        $notified = 0;

        foreach ($user->emergencyContacts as $contact) {
            try {
                $sms->send($contact->phone, $text);
                $notified++;
            } catch (Throwable $exception) {
                report($exception);
            }

            if ($contact->email) {
                Notification::route('mail', $contact->email)->notify(new SosRaised($alert));
            }
        }

        $alert->forceFill(['contacts_notified' => $notified])->save();

        Notification::send(User::where('role', Role::TourismOfficer)->whereNull('deactivated_at')->get(), new SosRaised($alert));

        Inertia::flash('toast', [
            'type' => 'warning',
            'message' => $notified > 0
                ? __('SOS sent to :count emergency contacts and the tourism office. If you are in danger, call 911.', ['count' => $notified])
                : __('SOS sent to the tourism office. Add emergency contacts so they are alerted too. If you are in danger, call 911.'),
        ]);

        return back();
    }
}

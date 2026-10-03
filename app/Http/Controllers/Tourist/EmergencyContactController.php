<?php

namespace App\Http\Controllers\Tourist;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tourist\EmergencyContactRequest;
use App\Models\EmergencyContact;
use App\Support\PhoneNumber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class EmergencyContactController extends Controller
{
    /**
     * The most contacts an SOS alert is sent to.
     */
    public const MAX_CONTACTS = 5;

    public function index(Request $request): Response
    {
        return Inertia::render('tourist/EmergencyContacts', [
            'contacts' => $request->user()->emergencyContacts()->oldest()->get(),
            'maxContacts' => self::MAX_CONTACTS,
        ]);
    }

    public function store(EmergencyContactRequest $request): RedirectResponse
    {
        if ($request->user()->emergencyContacts()->count() >= self::MAX_CONTACTS) {
            return back()->withErrors(['name' => __('You can save up to :count emergency contacts.', ['count' => self::MAX_CONTACTS])]);
        }

        $request->user()->emergencyContacts()->create([
            ...$request->validated(),
            'phone' => PhoneNumber::normalize($request->validated('phone')),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Emergency contact added.')]);

        return to_route('tourist.emergency-contacts.index');
    }

    public function update(EmergencyContactRequest $request, EmergencyContact $emergencyContact): RedirectResponse
    {
        Gate::authorize('update', $emergencyContact);

        $emergencyContact->update([
            ...$request->validated(),
            'phone' => PhoneNumber::normalize($request->validated('phone')),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Emergency contact updated.')]);

        return to_route('tourist.emergency-contacts.index');
    }

    public function destroy(EmergencyContact $emergencyContact): RedirectResponse
    {
        Gate::authorize('delete', $emergencyContact);

        $emergencyContact->delete();

        return to_route('tourist.emergency-contacts.index');
    }
}

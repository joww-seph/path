<?php

namespace App\Http\Controllers\Office;

use App\Enums\SosStatus;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\SosAlert;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * A live list of SOS alerts for tourism office staff. The page polls every 15 seconds.
 */
class SosMonitorController extends Controller
{
    public function index(): Response
    {
        $present = fn (SosAlert $alert) => [
            'id' => $alert->id,
            'status' => $alert->status->value,
            'status_label' => $alert->status->label(),
            'latitude' => $alert->latitude,
            'longitude' => $alert->longitude,
            'accuracy_meters' => $alert->accuracy_meters,
            'message' => $alert->message,
            'contacts_notified' => $alert->contacts_notified,
            'office_notes' => $alert->office_notes,
            'created_at' => $alert->created_at,
            'acknowledged_at' => $alert->acknowledged_at,
            'resolved_at' => $alert->resolved_at,
            'map_url' => $alert->mapUrl(),
            'user' => $alert->user->only(['id', 'name', 'phone', 'email']),
            'responder' => $alert->responder?->name,
            'trip' => $alert->trip?->only(['id', 'title']),
        ];

        return Inertia::render('office/SosMonitor', [
            'active' => SosAlert::with(['user:id,name,phone,email', 'responder:id,name', 'trip:id,title'])
                ->where('status', '!=', SosStatus::Resolved)
                ->latest()
                ->get()
                ->map($present),
            'resolved' => SosAlert::with(['user:id,name,phone,email', 'responder:id,name', 'trip:id,title'])
                ->where('status', SosStatus::Resolved)
                ->latest('resolved_at')
                ->limit(10)
                ->get()
                ->map($present),
        ]);
    }

    public function update(Request $request, SosAlert $alert): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:acknowledged,resolved'],
            'office_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $alert->status = SosStatus::from($validated['status']);
        $alert->office_notes = $validated['office_notes'] ?? $alert->office_notes;

        if ($alert->acknowledged_at === null) {
            $alert->acknowledged_at = now();
            $alert->acknowledged_by = $request->user()->id;
        }

        if ($alert->status === SosStatus::Resolved) {
            $alert->resolved_at = now();
        }

        $alert->save();

        ActivityLog::record("sos.{$validated['status']}", $alert);

        return back();
    }
}

<?php

namespace App\Http\Controllers\Office;

use App\Enums\VerificationStatus;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Business;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class BusinessVerificationController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'status' => ['nullable', Rule::enum(VerificationStatus::class)],
        ]);

        $status = $filters['status'] ?? VerificationStatus::Pending->value;

        return Inertia::render('office/Partners', [
            'businesses' => Business::with('owner:id,name,email,phone')
                ->withCount('listings')
                ->where('verification_status', $status)
                ->oldest()
                ->paginate(20)
                ->withQueryString()
                ->through(fn (Business $business) => [
                    ...$business->toArray(),
                    'type_label' => $business->type->label(),
                ]),
            'status' => $status,
            'counts' => Business::query()->toBase()
                ->selectRaw('verification_status, count(*) as total')
                ->groupBy('verification_status')
                ->pluck('total', 'verification_status'),
        ]);
    }

    public function update(Request $request, Business $business): RedirectResponse
    {
        Gate::authorize('verify', $business);

        $validated = $request->validate([
            'decision' => ['required', Rule::in(['approve', 'reject'])],
            'note' => ['nullable', 'required_if:decision,reject', 'string', 'max:255'],
        ]);

        $business->forceFill([
            'verification_status' => $validated['decision'] === 'approve' ? VerificationStatus::Approved : VerificationStatus::Rejected,
            'verification_note' => $validated['note'] ?? null,
            'verified_by' => $request->user()->id,
            'verified_at' => now(),
        ])->save();

        ActivityLog::record("business.{$validated['decision']}d", $business, ['note' => $validated['note'] ?? null]);

        Inertia::flash('toast', ['type' => 'success', 'message' => $validated['decision'] === 'approve'
            ? __(':name is now a verified partner.', ['name' => $business->name])
            : __(':name was not approved.', ['name' => $business->name])]);

        return back();
    }
}

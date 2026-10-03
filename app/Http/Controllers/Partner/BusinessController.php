<?php

namespace App\Http\Controllers\Partner;

use App\Enums\BusinessType;
use App\Enums\VerificationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Partner\UpdateBusinessRequest;
use App\Models\ActivityLog;
use App\Models\Business;
use App\Support\PhoneNumber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class BusinessController extends Controller
{
    public function edit(Request $request): Response
    {
        return Inertia::render('partner/Business', [
            'business' => $this->business($request),
            'businessTypes' => BusinessType::options(),
        ]);
    }

    /**
     * Update the business profile. A rejected business goes back into the verification queue.
     */
    public function update(UpdateBusinessRequest $request): RedirectResponse
    {
        $business = $this->business($request);

        Gate::authorize('update', $business);

        $business->fill([
            ...$request->validated(),
            'contact_phone' => PhoneNumber::normalize($request->validated('contact_phone')),
        ]);

        if ($business->verification_status === VerificationStatus::Rejected) {
            $business->verification_status = VerificationStatus::Pending;
            $business->verification_note = null;
        }

        $business->save();

        ActivityLog::record('business.updated', $business);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Business profile saved.')]);

        return to_route('partner.business.edit');
    }

    private function business(Request $request): Business
    {
        return $request->user()->businesses()->firstOrFail();
    }
}

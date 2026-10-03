<?php

namespace App\Http\Controllers\Office;

use App\Enums\AdvisorySeverity;
use App\Http\Controllers\Controller;
use App\Jobs\NotifyTravellersOfAdvisory;
use App\Models\ActivityLog;
use App\Models\Advisory;
use App\Models\Listing;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AdvisoryController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('office/Advisories', [
            'advisories' => Advisory::with('listings:id,name')
                ->orderByRaw('case when ends_at is null or ends_at >= ? then 0 else 1 end', [now()])
                ->latest('starts_at')
                ->paginate(20)
                ->through(fn (Advisory $advisory) => [
                    ...$advisory->only(['id', 'title', 'body', 'starts_at', 'ends_at', 'notified_at']),
                    'severity' => $advisory->severity->value,
                    'listing_ids' => $advisory->listings->pluck('id'),
                    'listings' => $advisory->listings->pluck('name'),
                ]),
            'listings' => Listing::published()->orderBy('name')->get(['id', 'name']),
            'severities' => AdvisorySeverity::options(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request);

        $advisory = new Advisory($validated);
        $advisory->author_id = $request->user()->id;
        $advisory->save();
        $advisory->listings()->sync($validated['listing_ids'] ?? []);

        Cache::forget('advisories:town-wide');
        ActivityLog::record('advisory.published', $advisory);
        NotifyTravellersOfAdvisory::dispatch($advisory);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Advisory published. Tourists travelling on those dates are being notified.')]);

        return back();
    }

    public function update(Request $request, Advisory $advisory): RedirectResponse
    {
        $validated = $this->validated($request);

        $advisory->update($validated);
        $advisory->listings()->sync($validated['listing_ids'] ?? []);

        Cache::forget('advisories:town-wide');
        ActivityLog::record('advisory.updated', $advisory);

        return back();
    }

    public function destroy(Advisory $advisory): RedirectResponse
    {
        ActivityLog::record('advisory.deleted', $advisory, ['title' => $advisory->title]);

        $advisory->delete();
        Cache::forget('advisories:town-wide');

        return back();
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'body' => ['required', 'string', 'max:3000'],
            'severity' => ['required', Rule::enum(AdvisorySeverity::class)],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
            'listing_ids' => ['nullable', 'array'],
            'listing_ids.*' => ['integer', Rule::exists('listings', 'id')],
        ]);
    }
}

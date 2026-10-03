<?php

namespace App\Http\Controllers\Partner;

use App\Actions\Listings\SaveListing;
use App\Enums\ListingStatus;
use App\Enums\RateUnit;
use App\Http\Controllers\Controller;
use App\Http\Requests\Listings\ListingRequest;
use App\Http\Resources\ListingCardResource;
use App\Http\Resources\ListingResource;
use App\Models\ActivityLog;
use App\Models\Business;
use App\Models\Category;
use App\Models\Listing;
use App\Support\Barangays;
use App\Support\Geo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Partners manage their own listings. New listings stay drafts until the tourism office approves them.
 */
class ListingController extends Controller
{
    public function index(Request $request): Response
    {
        $business = $this->business($request);

        return Inertia::render('partner/listings/Index', [
            'business' => $business->only(['id', 'name', 'verification_status']),
            'listings' => ListingCardResource::collection(
                $business->listings()->with(['category', 'coverPhoto', 'rates'])->latest()->get(),
            )->resolve(),
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('partner/listings/Edit', [
            ...$this->formOptions(),
            'listing' => null,
            'business' => $this->business($request)->only(['id', 'name', 'verification_status']),
        ]);
    }

    public function store(ListingRequest $request, SaveListing $saveListing): RedirectResponse
    {
        Gate::authorize('create', Listing::class);

        $listing = new Listing;
        $listing->business_id = $this->business($request)->id;
        $listing->status = ListingStatus::Draft;

        $saveListing->handle($listing, $request);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Draft saved. Add photos and rates, then submit it for approval.')]);

        return to_route('partner.listings.edit', $listing);
    }

    public function edit(Request $request, Listing $listing): Response
    {
        Gate::authorize('update', $listing);

        $listing->load(['category', 'business', 'rates', 'photos']);

        return Inertia::render('partner/listings/Edit', [
            ...$this->formOptions(),
            'listing' => (new ListingResource($listing))->resolve(),
            'business' => $this->business($request)->only(['id', 'name', 'verification_status']),
        ]);
    }

    public function update(ListingRequest $request, Listing $listing, SaveListing $saveListing): RedirectResponse
    {
        Gate::authorize('update', $listing);

        $saveListing->handle($listing, $request);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Listing saved.')]);

        return to_route('partner.listings.edit', $listing);
    }

    public function submit(Listing $listing): RedirectResponse
    {
        Gate::authorize('submit', $listing);

        $listing->forceFill(['status' => ListingStatus::Pending, 'review_note' => null])->save();

        ActivityLog::record('listing.submitted', $listing);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Sent to the tourism office for approval.')]);

        return back();
    }

    public function destroy(Listing $listing): RedirectResponse
    {
        Gate::authorize('delete', $listing);

        $listing->delete();

        return to_route('partner.listings.index');
    }

    private function business(Request $request): Business
    {
        return $request->user()->businesses()->firstOrFail();
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        return [
            'categories' => Category::orderBy('position')->get(['id', 'name', 'slug']),
            'barangays' => Barangays::all(),
            'rateUnits' => RateUnit::options(),
            'center' => Geo::PAOAY_CENTER,
            'canFeature' => false,
        ];
    }
}

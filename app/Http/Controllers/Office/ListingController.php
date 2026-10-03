<?php

namespace App\Http\Controllers\Office;

use App\Actions\Listings\SaveListing;
use App\Enums\ListingStatus;
use App\Enums\RateUnit;
use App\Http\Controllers\Controller;
use App\Http\Requests\Listings\ListingRequest;
use App\Http\Resources\ListingCardResource;
use App\Http\Resources\ListingResource;
use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Listing;
use App\Support\Barangays;
use App\Support\Geo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The tourism office manages public attractions and approves partner listings.
 */
class ListingController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::enum(ListingStatus::class)],
            'category' => ['nullable', 'integer'],
        ]);

        $listings = Listing::query()
            ->with(['category', 'coverPhoto', 'business:id,name'])
            ->search($filters['q'] ?? null)
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['category'] ?? null, fn ($query, $category) => $query->where('category_id', $category))
            ->orderByRaw("case when status = 'pending' then 0 else 1 end")
            ->latest('updated_at')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('office/listings/Index', [
            'listings' => ListingCardResource::collection($listings),
            'filters' => (object) $filters,
            'categories' => Category::orderBy('position')->get(['id', 'name']),
            'pendingCount' => Listing::where('status', ListingStatus::Pending)->count(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('office/listings/Edit', [...$this->formOptions(), 'listing' => null]);
    }

    public function store(ListingRequest $request, SaveListing $saveListing): RedirectResponse
    {
        $listing = new Listing;
        $listing->status = ListingStatus::Published;
        $listing->published_at = now();

        $saveListing->handle($listing, $request);

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name published.', ['name' => $listing->name])]);

        return to_route('office.listings.edit', $listing);
    }

    public function edit(Listing $listing): Response
    {
        $listing->load(['category', 'business', 'rates', 'photos', 'heritageStories']);

        return Inertia::render('office/listings/Edit', [
            ...$this->formOptions(),
            'listing' => (new ListingResource($listing))->resolve(),
        ]);
    }

    public function update(ListingRequest $request, Listing $listing, SaveListing $saveListing): RedirectResponse
    {
        $saveListing->handle($listing, $request);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Listing saved.')]);

        return to_route('office.listings.edit', $listing);
    }

    /**
     * Approve a partner listing so it goes live, or send it back with a note.
     */
    public function review(Request $request, Listing $listing): RedirectResponse
    {
        Gate::authorize('review', $listing);

        $validated = $request->validate([
            'decision' => ['required', Rule::in(['approve', 'reject'])],
            'note' => ['nullable', 'required_if:decision,reject', 'string', 'max:255'],
        ]);

        if ($validated['decision'] === 'approve') {
            $listing->forceFill(['status' => ListingStatus::Published, 'published_at' => now(), 'review_note' => null])->save();
        } else {
            $listing->forceFill(['status' => ListingStatus::Rejected, 'review_note' => $validated['note']])->save();
        }

        ActivityLog::record("listing.{$validated['decision']}d", $listing, ['note' => $validated['note'] ?? null]);

        Inertia::flash('toast', ['type' => 'success', 'message' => $validated['decision'] === 'approve'
            ? __(':name is now live.', ['name' => $listing->name])
            : __(':name was sent back to the partner.', ['name' => $listing->name])]);

        return back();
    }

    /**
     * Hide a published listing without deleting its history.
     */
    public function archive(Listing $listing): RedirectResponse
    {
        $listing->forceFill(['status' => $listing->status === ListingStatus::Archived ? ListingStatus::Published : ListingStatus::Archived])->save();

        ActivityLog::record($listing->status === ListingStatus::Archived ? 'listing.archived' : 'listing.restored', $listing);

        return back();
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
            'canFeature' => true,
        ];
    }
}

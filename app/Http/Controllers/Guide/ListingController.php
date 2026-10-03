<?php

namespace App\Http\Controllers\Guide;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Resources\ListingCardResource;
use App\Http\Resources\ListingResource;
use App\Models\Listing;
use App\Models\Review;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ListingController extends Controller
{
    private const NEARBY_COUNT = 4;

    /**
     * A listing's detail page. Drafts are visible only to their partner and the tourism office.
     */
    public function show(Request $request, Listing $listing): Response
    {
        Gate::authorize('view', $listing);

        $listing->load(['category', 'business', 'rates' => fn ($query) => $query->where('is_active', true), 'photos', 'coverPhoto', 'heritageStories']);

        return Inertia::render('guide/Listing', [
            'listing' => (new ListingResource($listing))->resolve(),
            'events' => $listing->events()->upcoming()->orderBy('starts_at')->limit(3)->get(['id', 'title', 'slug', 'starts_at', 'ends_at']),
            'nearby' => ListingCardResource::collection($this->nearby($listing))->resolve(),
            'paymentInstructions' => $listing->business?->payment_instructions,
            'myTrips' => $this->tripsFor($request->user()),
            'availability' => $listing->is_bookable ? $this->availability($listing) : null,
            'advisories' => $listing->advisories()->activeBetween(now(), now()->addDays(30))->orderBy('starts_at')->get(['advisories.id', 'title', 'body', 'severity', 'starts_at', 'ends_at']),
            'reviews' => $listing->reviews()->published()->with('user:id,name')->latest()->limit(20)->get()->map(fn (Review $review) => [
                'id' => $review->id,
                'rating' => $review->rating,
                'comment' => $review->comment,
                'partner_reply' => $review->partner_reply,
                'created_at' => $review->created_at,
                'author' => $review->user->name,
                'is_mine' => $review->user_id === $request->user()?->id,
            ]),
            'canReview' => $request->user() !== null && Gate::allows('create', [Review::class, $listing]),
            'myReview' => $request->user() ? $listing->reviews()->where('user_id', $request->user()->id)->first(['id', 'rating', 'comment', 'status']) : null,
        ]);
    }

    /**
     * Free slots for each of the next 60 days: null when unlimited, 0 when full or closed.
     *
     * @return array<string, int|null>
     */
    private function availability(Listing $listing): array
    {
        $blocks = $listing->availability()
            ->whereDate('date', '>=', now()->toDateString())->whereDate('date', '<=', now()->addDays(60)->toDateString())
            ->get()
            ->keyBy(fn ($block) => $block->date->toDateString());

        return collect(range(0, 60))->mapWithKeys(function (int $offset) use ($listing, $blocks) {
            $date = now()->addDays($offset)->toDateString();
            $block = $blocks[$date] ?? null;

            if ($block?->is_closed || ($listing->opening_hours !== null && ! $listing->isOpenOn(now()->addDays($offset)))) {
                return [$date => 0];
            }

            $total = $block?->slots_total ?? $listing->default_daily_slots;

            return [$date => $total === null ? null : max(0, $total - ($block->slots_booked ?? 0))];
        })->all();
    }

    /**
     * Upcoming trips the visitor can add this listing to.
     *
     * @return list<array{id: int, title: string, start_date: string, day_count: int}>|null
     */
    private function tripsFor(?User $user): ?array
    {
        if ($user === null || $user->role !== Role::Tourist) {
            return null;
        }

        return Trip::accessibleBy($user)
            ->where('end_date', '>=', now()->toDateString())
            ->orderBy('start_date')
            ->get()
            ->filter(fn (Trip $trip) => Gate::forUser($user)->allows('update', $trip))
            ->map(fn (Trip $trip) => [
                'id' => $trip->id,
                'title' => $trip->title,
                'start_date' => $trip->start_date->toDateString(),
                'day_count' => $trip->dayCount(),
            ])
            ->values()
            ->all();
    }

    /**
     * @return Collection<int, Listing>
     */
    private function nearby(Listing $listing)
    {
        if (! $listing->hasLocation()) {
            return collect();
        }

        return Listing::published()
            ->whereKeyNot($listing->id)
            ->whereNotNull('latitude')
            ->with(['category', 'coverPhoto'])
            ->get()
            ->each(fn (Listing $other) => $other->setAttribute('distance_km', $other->distanceKmFrom($listing->latitude, $listing->longitude)))
            ->sortBy('distance_km')
            ->take(self::NEARBY_COUNT)
            ->values();
    }
}

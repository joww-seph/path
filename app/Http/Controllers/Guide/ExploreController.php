<?php

namespace App\Http\Controllers\Guide;

use App\Http\Controllers\Controller;
use App\Http\Resources\ListingCardResource;
use App\Models\Category;
use App\Models\Listing;
use App\Support\Barangays;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ExploreController extends Controller
{
    private const PER_PAGE = 12;

    /**
     * Browse and filter published listings.
     */
    public function __invoke(Request $request): Response
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'string', Rule::exists('categories', 'slug')],
            'barangay' => ['nullable', Rule::in(Barangays::slugs())],
            'max_price' => ['nullable', 'numeric', 'min:0'],
            'min_rating' => ['nullable', 'numeric', 'between:1,5'],
            'accessible' => ['nullable', 'boolean'],
            'bookable' => ['nullable', 'boolean'],
            'open_on' => ['nullable', 'date_format:Y-m-d'],
            'sort' => ['nullable', Rule::in(['featured', 'name', 'rating', 'price', 'distance'])],
            'lat' => ['nullable', 'numeric', 'between:-90,90', 'required_if:sort,distance'],
            'lng' => ['nullable', 'numeric', 'between:-180,180', 'required_if:sort,distance'],
        ]);

        $query = Listing::published()
            ->with(['category', 'coverPhoto', 'rates'])
            ->search($filters['q'] ?? null)
            ->when($filters['category'] ?? null, fn (Builder $query, string $slug) => $query->whereRelation('category', 'slug', $slug))
            ->when($filters['barangay'] ?? null, fn (Builder $query, string $barangay) => $query->where('barangay', $barangay))
            ->when($filters['min_rating'] ?? null, fn (Builder $query, $rating) => $query->where('rating_average', '>=', $rating))
            ->when($request->boolean('accessible'), fn (Builder $query) => $query->where('is_accessible', true))
            ->when($request->boolean('bookable'), fn (Builder $query) => $query->where('is_bookable', true))
            ->when(isset($filters['max_price']), fn (Builder $query) => $this->filterByMaxPrice($query, (float) $filters['max_price']));

        $sort = $filters['sort'] ?? 'featured';

        // Opening days and distances are worked out in PHP, so those filters page through a collection.
        // A town's worth of listings is small enough for that, and it works on any database.
        $listings = $sort === 'distance' || isset($filters['open_on'])
            ? $this->paginateInMemory($this->sorted($query, $sort === 'distance' ? 'name' : $sort)->get(), $filters, $request)
            : $this->sorted($query, $sort)->paginate(self::PER_PAGE)->withQueryString();

        return Inertia::render('guide/Explore', [
            'listings' => ListingCardResource::collection($listings),
            'filters' => (object) $filters,
            'categories' => Category::orderBy('position')->get(['id', 'name', 'slug', 'icon', 'color']),
            'barangays' => Barangays::all(),
        ]);
    }

    /**
     * @param  Builder<Listing>  $query
     */
    private function filterByMaxPrice(Builder $query, float $maxPrice): void
    {
        $query->where(fn (Builder $query) => $query
            ->where('entrance_fee', '<=', $maxPrice)
            ->orWhere('price_min', '<=', $maxPrice)
            ->orWhereHas('rates', fn (Builder $query) => $query->where('is_active', true)->where('price', '<=', $maxPrice))
            ->orWhere(fn (Builder $query) => $query->whereNull('entrance_fee')->whereNull('price_min')->whereDoesntHave('rates')));
    }

    /**
     * @param  Builder<Listing>  $query
     * @return Builder<Listing>
     */
    private function sorted(Builder $query, string $sort): Builder
    {
        return match ($sort) {
            'name' => $query->orderBy('name'),
            'rating' => $query->orderByDesc('rating_average')->orderByDesc('reviews_count'),
            'price' => $query->orderByRaw('coalesce(entrance_fee, price_min, 999999)')->orderBy('name'),
            default => $query->orderByDesc('is_featured')->orderByDesc('rating_average')->orderBy('name'),
        };
    }

    /**
     * @param  Collection<int, Listing>  $listings
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Listing>
     */
    private function paginateInMemory(Collection $listings, array $filters, Request $request): LengthAwarePaginator
    {
        if (isset($filters['open_on'])) {
            $date = CarbonImmutable::parse($filters['open_on']);
            $listings = $listings->filter(fn (Listing $listing) => $listing->isOpenOn($date));
        }

        if (($filters['sort'] ?? null) === 'distance') {
            $listings = $listings
                ->filter(fn (Listing $listing) => $listing->hasLocation())
                ->each(fn (Listing $listing) => $listing->setAttribute('distance_km', $listing->distanceKmFrom((float) $filters['lat'], (float) $filters['lng'])))
                ->sortBy('distance_km');
        }

        $listings = $listings->values();
        $page = LengthAwarePaginator::resolveCurrentPage();

        return (new LengthAwarePaginator(
            $listings->forPage($page, self::PER_PAGE)->values(),
            $listings->count(),
            self::PER_PAGE,
            $page,
            ['path' => $request->url()],
        ))->withQueryString();
    }
}

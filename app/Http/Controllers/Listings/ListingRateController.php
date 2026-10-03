<?php

namespace App\Http\Controllers\Listings;

use App\Enums\RateUnit;
use App\Http\Controllers\Controller;
use App\Models\Listing;
use App\Models\ListingRate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * The prices a partner (or the tourism office) sets on a listing.
 */
class ListingRateController extends Controller
{
    public function store(Request $request, Listing $listing): RedirectResponse
    {
        Gate::authorize('update', $listing);

        $listing->rates()->create([
            ...$this->validated($request),
            'position' => (int) $listing->rates()->max('position') + 1,
        ]);

        return back();
    }

    public function update(Request $request, Listing $listing, ListingRate $rate): RedirectResponse
    {
        Gate::authorize('update', $listing);

        $rate->update($this->validated($request));

        return back();
    }

    public function destroy(Listing $listing, ListingRate $rate): RedirectResponse
    {
        Gate::authorize('update', $listing);

        $rate->delete();

        return back();
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0', 'max:1000000'],
            'unit' => ['required', Rule::enum(RateUnit::class)],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'is_active' => ['boolean'],
        ]);

        return [...$validated, 'is_active' => $request->boolean('is_active', true)];
    }
}

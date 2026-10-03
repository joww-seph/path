<?php

namespace App\Http\Controllers\Listings;

use App\Http\Controllers\Controller;
use App\Models\Listing;
use App\Models\ListingPhoto;
use App\Services\ImageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

/**
 * Photo uploads for a listing, shared by partners and the tourism office.
 */
class ListingPhotoController extends Controller
{
    public const MAX_PHOTOS = 12;

    public function store(Request $request, Listing $listing, ImageService $images): RedirectResponse
    {
        Gate::authorize('update', $listing);

        $remaining = self::MAX_PHOTOS - $listing->photos()->count();

        $validated = $request->validate([
            'photos' => ['required', 'array', 'min:1', "max:{$remaining}"],
            'photos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:8192', 'dimensions:min_width=400,min_height=300'],
        ], [
            'photos.max' => __('A listing can have up to :max photos.', ['max' => self::MAX_PHOTOS]),
        ]);

        $position = (int) $listing->photos()->max('position');

        foreach ($validated['photos'] as $file) {
            $listing->photos()->create([
                ...$images->storeResized($file, "listings/{$listing->id}"),
                'position' => ++$position,
            ]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Photos uploaded.')]);

        return back();
    }

    public function update(Request $request, Listing $listing, ListingPhoto $photo): RedirectResponse
    {
        Gate::authorize('update', $listing);

        $validated = $request->validate([
            'caption' => ['nullable', 'string', 'max:200'],
            'make_cover' => ['boolean'],
        ]);

        $photo->caption = $validated['caption'] ?? $photo->caption;

        if ($request->boolean('make_cover')) {
            $photo->position = (int) $listing->photos()->min('position') - 1;
        }

        $photo->save();

        return back();
    }

    public function destroy(Listing $listing, ListingPhoto $photo, ImageService $images): RedirectResponse
    {
        Gate::authorize('update', $listing);

        $images->delete($photo->path, $photo->thumbnail_path);
        $photo->delete();

        return back();
    }
}

<?php

namespace App\Http\Controllers\Tourist;

use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Models\Listing;
use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class ReviewController extends Controller
{
    public function store(Request $request, Listing $listing): RedirectResponse
    {
        Gate::authorize('create', [Review::class, $listing]);

        $review = new Review($this->validated($request));
        $review->user_id = $request->user()->id;
        $review->listing_id = $listing->id;
        $review->booking_id = $request->user()->bookings()
            ->where('listing_id', $listing->id)
            ->where('status', BookingStatus::Completed)
            ->latest('checked_in_at')
            ->value('id');
        $review->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Thank you! Your review is published.')]);

        return back();
    }

    public function update(Request $request, Review $review): RedirectResponse
    {
        Gate::authorize('update', $review);

        $review->update($this->validated($request));

        return back();
    }

    public function destroy(Review $review): RedirectResponse
    {
        Gate::authorize('delete', $review);

        $review->delete();

        return back();
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ]);
    }
}

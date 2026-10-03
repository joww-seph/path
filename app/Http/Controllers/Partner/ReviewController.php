<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ReviewController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('partner/Reviews', [
            'reviews' => Review::published()
                ->whereHas('listing.business', fn ($query) => $query->where('owner_id', $request->user()->id))
                ->with(['user:id,name', 'listing:id,name,slug'])
                ->latest()
                ->paginate(20)
                ->through(fn (Review $review) => [
                    'id' => $review->id,
                    'rating' => $review->rating,
                    'comment' => $review->comment,
                    'partner_reply' => $review->partner_reply,
                    'created_at' => $review->created_at,
                    'author' => $review->user->name,
                    'listing' => $review->listing->only(['name', 'slug']),
                ]),
        ]);
    }

    public function reply(Request $request, Review $review): RedirectResponse
    {
        Gate::authorize('reply', $review);

        $validated = $request->validate(['partner_reply' => ['nullable', 'string', 'max:2000']]);

        $review->forceFill([
            'partner_reply' => $validated['partner_reply'],
            'partner_replied_at' => $validated['partner_reply'] ? now() : null,
        ])->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Reply saved.')]);

        return back();
    }
}

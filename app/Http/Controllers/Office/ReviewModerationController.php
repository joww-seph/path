<?php

namespace App\Http\Controllers\Office;

use App\Enums\ReviewStatus;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The tourism office and administrators hide reviews that break the rules (abuse, spam, personal data).
 */
class ReviewModerationController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('moderate', Review::class);

        $filters = $request->validate([
            'status' => ['nullable', Rule::enum(ReviewStatus::class)],
            'max_rating' => ['nullable', 'integer', 'between:1,5'],
        ]);

        return Inertia::render('office/Reviews', [
            'reviews' => Review::query()
                ->with(['user:id,name,email', 'listing:id,name,slug'])
                ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
                ->when($filters['max_rating'] ?? null, fn ($query, $rating) => $query->where('rating', '<=', $rating))
                ->latest()
                ->paginate(20)
                ->withQueryString()
                ->through(fn (Review $review) => [
                    'id' => $review->id,
                    'rating' => $review->rating,
                    'comment' => $review->comment,
                    'partner_reply' => $review->partner_reply,
                    'status' => $review->status->value,
                    'moderation_note' => $review->moderation_note,
                    'created_at' => $review->created_at,
                    'author' => $review->user->only(['name', 'email']),
                    'listing' => $review->listing->only(['name', 'slug']),
                ]),
            'filters' => (object) $filters,
        ]);
    }

    public function update(Request $request, Review $review): RedirectResponse
    {
        Gate::authorize('moderate', Review::class);

        $validated = $request->validate([
            'status' => ['required', Rule::enum(ReviewStatus::class)],
            'moderation_note' => ['nullable', 'required_if:status,hidden', 'string', 'max:255'],
        ]);

        $review->forceFill([
            'status' => $validated['status'],
            'moderation_note' => $validated['status'] === ReviewStatus::Hidden->value ? $validated['moderation_note'] : null,
        ])->save();

        ActivityLog::record("review.{$validated['status']}", $review, ['note' => $validated['moderation_note'] ?? null]);

        return back();
    }
}

<?php

namespace App\Models;

use App\Enums\ReviewStatus;
use Database\Factories\ReviewFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property int $listing_id
 * @property int|null $booking_id
 * @property int $rating
 * @property string|null $comment
 * @property string|null $partner_reply
 * @property Carbon|null $partner_replied_at
 * @property ReviewStatus $status
 * @property string|null $moderation_note
 * @property-read User $user
 * @property-read Listing $listing
 */
#[Fillable(['rating', 'comment'])]
class Review extends Model
{
    /** @use HasFactory<ReviewFactory> */
    use HasFactory;

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'published',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'status' => ReviewStatus::class,
            'partner_replied_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        // Keep the listing's average rating and review count in step with published reviews.
        $refresh = fn (Review $review) => $review->listing?->refreshRating();

        static::saved($refresh);
        static::deleted($refresh);
    }

    /**
     * @param  Builder<Review>  $query
     */
    public function scopePublished(Builder $query): void
    {
        $query->where('status', ReviewStatus::Published);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Listing, $this>
     */
    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }

    /**
     * @return BelongsTo<Booking, $this>
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
}

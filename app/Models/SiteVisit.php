<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A tourist's visit to a listing on a date, from a ticked itinerary stop or a completed booking.
 * Visits unlock reviews and feed the tourism office's analytics.
 *
 * visited_on is kept as a plain "Y-m-d" string so the unique (listing, user, day) index works the
 * same on every database.
 *
 * @property int $id
 * @property int $listing_id
 * @property int $user_id
 * @property string $visited_on
 * @property string $source
 */
#[Fillable(['listing_id', 'user_id', 'visited_on', 'source'])]
class SiteVisit extends Model
{
    public static function record(int $listingId, int $userId, string $date, string $source): self
    {
        return self::firstOrCreate(
            ['listing_id' => $listingId, 'user_id' => $userId, 'visited_on' => $date],
            ['source' => $source],
        );
    }

    /**
     * @return BelongsTo<Listing, $this>
     */
    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

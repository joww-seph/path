<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A partner's capacity on one date. slots_total null means the listing's default daily slots apply.
 *
 * @property int $id
 * @property int $listing_id
 * @property CarbonImmutable $date
 * @property int|null $slots_total
 * @property int $slots_booked
 * @property bool $is_closed
 */
#[Fillable(['listing_id', 'date', 'slots_total', 'slots_booked', 'is_closed'])]
class AvailabilityBlock extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'immutable_date',
            'slots_total' => 'integer',
            'slots_booked' => 'integer',
            'is_closed' => 'boolean',
        ];
    }

    /**
     * The row for a listing and date, created if missing. Dates are matched by day because the
     * column stores a time part on some databases.
     */
    public static function forDate(int $listingId, CarbonInterface|string $date): self
    {
        $day = is_string($date) ? $date : $date->toDateString();

        return self::where('listing_id', $listingId)->whereDate('date', $day)->first()
            ?? self::create(['listing_id' => $listingId, 'date' => $day]);
    }

    /**
     * @return BelongsTo<Listing, $this>
     */
    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }
}

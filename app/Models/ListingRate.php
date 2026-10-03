<?php

namespace App\Models;

use App\Enums\RateUnit;
use Database\Factories\ListingRateFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A price a partner charges, e.g. "4x4 ride, up to 5 people — ₱2,500 per ride".
 *
 * @property int $id
 * @property int $listing_id
 * @property string $name
 * @property string|null $description
 * @property string $price
 * @property RateUnit $unit
 * @property int|null $capacity
 * @property bool $is_active
 * @property int $position
 * @property-read Listing $listing
 */
#[Fillable(['name', 'description', 'price', 'unit', 'capacity', 'is_active', 'position'])]
class ListingRate extends Model
{
    /** @use HasFactory<ListingRateFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'unit' => RateUnit::class,
            'capacity' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Listing, $this>
     */
    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }
}

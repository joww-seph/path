<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $itinerary_template_id
 * @property int|null $listing_id
 * @property string|null $custom_title
 * @property int $day_number
 * @property int $position
 * @property int|null $duration_minutes
 * @property string|null $notes
 * @property-read Listing|null $listing
 */
#[Fillable(['listing_id', 'custom_title', 'day_number', 'position', 'duration_minutes', 'notes'])]
class TemplateItem extends Model
{
    /**
     * @return BelongsTo<Listing, $this>
     */
    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }
}

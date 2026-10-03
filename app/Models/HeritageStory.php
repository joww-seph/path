<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $listing_id
 * @property string $title
 * @property string $body
 * @property string|null $source
 * @property int $position
 */
#[Fillable(['title', 'body', 'source', 'position'])]
class HeritageStory extends Model
{
    /**
     * @return BelongsTo<Listing, $this>
     */
    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }
}

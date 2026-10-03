<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Appends;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property int $listing_id
 * @property string $path
 * @property string $thumbnail_path
 * @property string|null $caption
 * @property int $position
 * @property-read string $url
 * @property-read string $thumbnail_url
 */
#[Fillable(['path', 'thumbnail_path', 'caption', 'position'])]
#[Appends(['url', 'thumbnail_url'])]
class ListingPhoto extends Model
{
    protected function url(): Attribute
    {
        return Attribute::get(fn (): string => self::publicUrl($this->path));
    }

    protected function thumbnailUrl(): Attribute
    {
        return Attribute::get(fn (): string => self::publicUrl($this->thumbnail_path));
    }

    /**
     * Seeded photos live in public/; uploads live on the public disk.
     */
    private static function publicUrl(string $path): string
    {
        return str_starts_with($path, '/') ? $path : Storage::disk('public')->url($path);
    }

    /**
     * @return BelongsTo<Listing, $this>
     */
    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }
}

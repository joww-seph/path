<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\EventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * A festival, feast day or local activity on the events calendar.
 *
 * @property int $id
 * @property string $title
 * @property string $slug
 * @property string|null $description
 * @property int|null $venue_listing_id
 * @property string|null $venue_name
 * @property Carbon $starts_at
 * @property Carbon|null $ends_at
 * @property bool $is_featured
 * @property string|null $image_path
 * @property int|null $created_by
 * @property-read Listing|null $venue
 */
#[Fillable(['title', 'description', 'venue_listing_id', 'venue_name', 'starts_at', 'ends_at', 'is_featured'])]
class Event extends Model
{
    /** @use HasFactory<EventFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_featured' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Event $event) {
            if ($event->slug !== null) {
                return;
            }

            $base = Str::slug($event->title).'-'.$event->starts_at->format('Y');
            $slug = $base;
            $suffix = 2;

            while (static::where('slug', $slug)->exists()) {
                $slug = "{$base}-{$suffix}";
                $suffix++;
            }

            $event->slug = $slug;
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Events that have not finished yet.
     *
     * @param  Builder<Event>  $query
     */
    public function scopeUpcoming(Builder $query): void
    {
        $query->where(fn (Builder $query) => $query
            ->where('ends_at', '>=', now())
            ->orWhere(fn (Builder $query) => $query->whereNull('ends_at')->where('starts_at', '>=', now()->startOfDay())));
    }

    /**
     * Events that take place, at least partly, between two dates.
     *
     * @param  Builder<Event>  $query
     */
    public function scopeBetween(Builder $query, CarbonInterface $from, CarbonInterface $to): void
    {
        $query->where('starts_at', '<=', $to)
            ->where(fn (Builder $query) => $query
                ->where('ends_at', '>=', $from)
                ->orWhere(fn (Builder $query) => $query->whereNull('ends_at')->where('starts_at', '>=', $from)));
    }

    /**
     * @return BelongsTo<Listing, $this>
     */
    public function venue(): BelongsTo
    {
        return $this->belongsTo(Listing::class, 'venue_listing_id');
    }
}

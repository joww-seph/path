<?php

namespace App\Models;

use Database\Factories\ItineraryItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One stop on a trip: a PaTH listing, or a custom stop such as "Lunch at Lola's house".
 *
 * Start and end times are worked out by the ItineraryPlanner from the day's start time, the
 * stop order, visit lengths and travel time. A fixed start time pins a stop to a time of day.
 *
 * @property int $id
 * @property int $trip_id
 * @property int|null $listing_id
 * @property string|null $custom_title
 * @property float|null $custom_latitude
 * @property float|null $custom_longitude
 * @property int $day_number
 * @property int $position
 * @property int|null $duration_minutes
 * @property string|null $fixed_start_time
 * @property string|null $start_time
 * @property string|null $end_time
 * @property int|null $travel_minutes_from_previous
 * @property string|null $distance_km_from_previous
 * @property string|null $notes
 * @property bool $is_done
 * @property string|null $client_uuid
 * @property-read Trip $trip
 * @property-read Listing|null $listing
 */
#[Fillable(['listing_id', 'custom_title', 'custom_latitude', 'custom_longitude', 'day_number', 'position', 'duration_minutes', 'fixed_start_time', 'notes', 'is_done', 'client_uuid'])]
class ItineraryItem extends Model
{
    /** @use HasFactory<ItineraryItemFactory> */
    use HasFactory;

    public const DEFAULT_CUSTOM_MINUTES = 60;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'custom_latitude' => 'float',
            'custom_longitude' => 'float',
            'day_number' => 'integer',
            'position' => 'integer',
            'duration_minutes' => 'integer',
            'travel_minutes_from_previous' => 'integer',
            'distance_km_from_previous' => 'decimal:2',
            'is_done' => 'boolean',
        ];
    }

    public function title(): string
    {
        return $this->custom_title ?? $this->listing->name ?? 'Stop';
    }

    public function latitude(): ?float
    {
        return $this->listing_id !== null ? $this->listing?->latitude : $this->custom_latitude;
    }

    public function longitude(): ?float
    {
        return $this->listing_id !== null ? $this->listing?->longitude : $this->custom_longitude;
    }

    public function hasLocation(): bool
    {
        return $this->latitude() !== null && $this->longitude() !== null;
    }

    /**
     * How long the stop takes: the traveller's own estimate, or the listing's typical visit.
     */
    public function visitMinutes(): int
    {
        return $this->duration_minutes ?? $this->listing->visit_minutes ?? self::DEFAULT_CUSTOM_MINUTES;
    }

    /**
     * @return BelongsTo<Trip, $this>
     */
    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    /**
     * @return BelongsTo<Listing, $this>
     */
    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }
}

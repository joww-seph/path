<?php

namespace App\Http\Resources;

use App\Models\ItineraryItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ItineraryItem
 */
class ItineraryItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $listing = $this->listing;

        return [
            'id' => $this->id,
            'title' => $this->title(),
            'listing' => $listing === null ? null : [
                'id' => $listing->id,
                'name' => $listing->name,
                'slug' => $listing->slug,
                'category' => $listing->relationLoaded('category') ? [
                    'name' => $listing->category->name,
                    'icon' => $listing->category->icon,
                    'color' => $listing->category->color,
                ] : null,
                'photo' => $listing->relationLoaded('coverPhoto') ? $listing->coverPhoto?->thumbnail_url : null,
                'entrance_fee' => $listing->entrance_fee,
                'price_min' => $listing->price_min,
                'is_bookable' => $listing->is_bookable,
                'contact_phone' => $listing->contact_phone,
                'address' => $listing->address,
            ],
            'latitude' => $this->latitude(),
            'longitude' => $this->longitude(),
            'day_number' => $this->day_number,
            'position' => $this->position,
            'duration_minutes' => $this->duration_minutes,
            'visit_minutes' => $this->visitMinutes(),
            'fixed_start_time' => $this->fixed_start_time ? substr($this->fixed_start_time, 0, 5) : null,
            'start_time' => $this->start_time ? substr($this->start_time, 0, 5) : null,
            'end_time' => $this->end_time ? substr($this->end_time, 0, 5) : null,
            'travel_minutes_from_previous' => $this->travel_minutes_from_previous,
            'distance_km_from_previous' => $this->distance_km_from_previous !== null ? (float) $this->distance_km_from_previous : null,
            'notes' => $this->notes,
            'is_done' => $this->is_done,
            'updated_at' => $this->updated_at,
        ];
    }
}

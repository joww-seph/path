<?php

namespace App\Http\Resources;

use App\Models\Listing;
use App\Support\Barangays;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The short form of a listing used on cards, map markers and search results.
 *
 * @mixin Listing
 */
class ListingCardResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'summary' => $this->summary,
            'status' => $this->status->value,
            'category' => $this->whenLoaded('category', fn () => [
                'name' => $this->category->name,
                'slug' => $this->category->slug,
                'icon' => $this->category->icon,
                'color' => $this->category->color,
            ]),
            'business_name' => $this->whenLoaded('business', fn () => $this->business?->name),
            'barangay' => Barangays::name($this->barangay),
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'starting_price' => $this->starting_price,
            'is_free' => $this->entrance_fee !== null && (float) $this->entrance_fee === 0.0,
            'is_bookable' => $this->is_bookable,
            'is_accessible' => $this->is_accessible,
            'is_featured' => $this->is_featured,
            'rating_average' => (float) $this->rating_average,
            'reviews_count' => $this->reviews_count,
            'visit_minutes' => $this->visit_minutes,
            'photo' => $this->whenLoaded('coverPhoto', fn () => $this->coverPhoto?->thumbnail_url),
            'distance_km' => $this->when(isset($this->distance_km), fn () => round((float) $this->distance_km, 1)),
        ];
    }
}

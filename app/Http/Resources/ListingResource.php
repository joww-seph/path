<?php

namespace App\Http\Resources;

use App\Models\Listing;
use Illuminate\Http\Request;

/**
 * The full listing shown on its detail page and in edit forms.
 *
 * @mixin Listing
 */
class ListingResource extends ListingCardResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            ...parent::toArray($request),
            'category_id' => $this->category_id,
            'business_id' => $this->business_id,
            'description' => $this->description,
            'barangay_slug' => $this->barangay,
            'address' => $this->address,
            'opening_hours' => $this->opening_hours,
            'entrance_fee' => $this->entrance_fee,
            'price_min' => $this->price_min,
            'price_max' => $this->price_max,
            'contact_phone' => $this->contact_phone,
            'contact_email' => $this->contact_email,
            'website' => $this->website,
            'facebook_url' => $this->facebook_url,
            'review_note' => $this->review_note,
            'published_at' => $this->published_at,
            'business' => $this->whenLoaded('business', fn () => $this->business === null ? null : [
                'id' => $this->business->id,
                'name' => $this->business->name,
                'verification_status' => $this->business->verification_status->value,
            ]),
            'rates' => $this->whenLoaded('rates', fn () => $this->rates->map(fn ($rate) => [
                'id' => $rate->id,
                'name' => $rate->name,
                'description' => $rate->description,
                'price' => $rate->price,
                'unit' => $rate->unit->value,
                'unit_label' => $rate->unit->label(),
                'capacity' => $rate->capacity,
                'is_active' => $rate->is_active,
            ])),
            'photos' => $this->whenLoaded('photos', fn () => $this->photos->map(fn ($photo) => [
                'id' => $photo->id,
                'url' => $photo->url,
                'thumbnail_url' => $photo->thumbnail_url,
                'caption' => $photo->caption,
            ])),
            'heritage_stories' => $this->whenLoaded('heritageStories', fn () => $this->heritageStories->map->only(['id', 'title', 'body', 'source'])),
        ];
    }
}

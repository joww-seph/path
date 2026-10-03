<?php

namespace App\Http\Resources;

use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Booking
 */
class BookingResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'rate_name' => $this->rate_name,
            'unit' => $this->unit->value,
            'unit_label' => $this->unit->label(),
            'unit_price' => $this->unit_price,
            'date' => $this->date->toDateString(),
            'time' => $this->time ? substr($this->time, 0, 5) : null,
            'nights' => $this->nights,
            'pax' => $this->pax,
            'quantity' => $this->quantity,
            'total_amount' => $this->total_amount,
            'tourist_note' => $this->tourist_note,
            'partner_note' => $this->partner_note,
            'expires_at' => $this->expires_at,
            'confirmed_at' => $this->confirmed_at,
            'checked_in_at' => $this->checked_in_at,
            'cancelled_at' => $this->cancelled_at,
            'created_at' => $this->created_at,
            'is_cancellable' => $this->isCancellable(),
            'listing' => $this->whenLoaded('listing', fn () => [
                'id' => $this->listing->id,
                'name' => $this->listing->name,
                'slug' => $this->listing->slug,
                'address' => $this->listing->address,
                'contact_phone' => $this->listing->contact_phone ?? $this->listing->business?->contact_phone,
                'latitude' => $this->listing->latitude,
                'longitude' => $this->listing->longitude,
            ]),
            'tourist' => $this->whenLoaded('tourist', fn () => $this->tourist->only(['id', 'name', 'email', 'phone'])),
            'trip' => $this->whenLoaded('trip', fn () => $this->trip?->only(['id', 'title'])),
        ];
    }
}

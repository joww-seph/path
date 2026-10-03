<?php

namespace App\Http\Resources;

use App\Models\Trip;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Trip
 */
class TripResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'start_date' => $this->start_date->toDateString(),
            'end_date' => $this->end_date->toDateString(),
            'day_count' => $this->dayCount(),
            'pax' => $this->pax,
            'budget' => $this->budget,
            'day_starts_at' => substr($this->day_starts_at, 0, 5),
            'travel_mode' => $this->travel_mode->value,
            'notes' => $this->notes,
            'role' => $this->roleFor($request->user())?->value,
            'owner' => $this->whenLoaded('owner', fn () => $this->owner->only(['id', 'name'])),
            'items_count' => $this->whenCounted('items'),
            'is_shared' => $this->share_token !== null,
            'share_url' => $this->when(
                $this->share_token !== null && $this->roleFor($request->user())?->value === 'owner',
                fn () => route('trips.shared', $this->share_token),
            ),
        ];
    }
}

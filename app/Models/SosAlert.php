<?php

namespace App\Models;

use App\Enums\SosStatus;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property int|null $trip_id
 * @property float|null $latitude
 * @property float|null $longitude
 * @property int|null $accuracy_meters
 * @property string|null $message
 * @property SosStatus $status
 * @property int $contacts_notified
 * @property int|null $acknowledged_by
 * @property CarbonImmutable|null $acknowledged_at
 * @property CarbonImmutable|null $resolved_at
 * @property string|null $office_notes
 * @property CarbonImmutable $created_at
 * @property-read User $user
 */
#[Fillable(['trip_id', 'latitude', 'longitude', 'accuracy_meters', 'message'])]
class SosAlert extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'status' => SosStatus::class,
            'acknowledged_at' => 'immutable_datetime',
            'resolved_at' => 'immutable_datetime',
        ];
    }

    public function mapUrl(): ?string
    {
        return $this->latitude !== null
            ? "https://www.openstreetmap.org/?mlat={$this->latitude}&mlon={$this->longitude}#map=17/{$this->latitude}/{$this->longitude}"
            : null;
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Trip, $this>
     */
    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function responder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acknowledged_by');
    }
}

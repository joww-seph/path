<?php

namespace App\Models;

use App\Enums\TripRole;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @property int $trip_id
 * @property int $user_id
 * @property TripRole $role
 */
class TripMember extends Pivot
{
    protected $table = 'trip_members';

    public $incrementing = true;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'role' => TripRole::class,
        ];
    }
}

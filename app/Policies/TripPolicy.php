<?php

namespace App\Policies;

use App\Enums\TripRole;
use App\Models\Trip;
use App\Models\User;

class TripPolicy
{
    public function view(User $user, Trip $trip): bool
    {
        return $trip->roleFor($user) !== null;
    }

    /**
     * The owner and companions invited as editors can change the itinerary.
     */
    public function update(User $user, Trip $trip): bool
    {
        return in_array($trip->roleFor($user), [TripRole::Owner, TripRole::Editor], true);
    }

    /**
     * Only the owner can delete the trip, invite companions or share it by link.
     */
    public function manage(User $user, Trip $trip): bool
    {
        return $trip->roleFor($user) === TripRole::Owner;
    }
}

<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Business;
use App\Models\User;

class BusinessPolicy
{
    public function update(User $user, Business $business): bool
    {
        return $business->owner_id === $user->id;
    }

    /**
     * Only the tourism office (or an administrator) approves or rejects partners.
     */
    public function verify(User $user, Business $business): bool
    {
        return $user->hasRole(Role::TourismOfficer, Role::Admin);
    }
}

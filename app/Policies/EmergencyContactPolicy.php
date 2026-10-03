<?php

namespace App\Policies;

use App\Models\EmergencyContact;
use App\Models\User;

class EmergencyContactPolicy
{
    public function update(User $user, EmergencyContact $emergencyContact): bool
    {
        return $emergencyContact->user_id === $user->id;
    }

    public function delete(User $user, EmergencyContact $emergencyContact): bool
    {
        return $emergencyContact->user_id === $user->id;
    }
}

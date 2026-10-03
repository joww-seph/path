<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Booking;
use App\Models\User;

class BookingPolicy
{
    public function view(User $user, Booking $booking): bool
    {
        return $booking->user_id === $user->id
            || $this->ownsListing($user, $booking)
            || $user->hasRole(Role::TourismOfficer, Role::Admin);
    }

    /**
     * The partner who owns the listing accepts, declines and checks in bookings.
     */
    public function respond(User $user, Booking $booking): bool
    {
        return $this->ownsListing($user, $booking);
    }

    public function cancel(User $user, Booking $booking): bool
    {
        return $booking->user_id === $user->id || $this->ownsListing($user, $booking);
    }

    private function ownsListing(User $user, Booking $booking): bool
    {
        return $booking->listing?->business?->owner_id === $user->id;
    }
}

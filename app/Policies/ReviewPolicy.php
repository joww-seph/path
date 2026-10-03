<?php

namespace App\Policies;

use App\Enums\BookingStatus;
use App\Enums\Role;
use App\Models\Listing;
use App\Models\Review;
use App\Models\SiteVisit;
use App\Models\User;

class ReviewPolicy
{
    /**
     * Only tourists who completed a booking or ticked off a visit can review a place, once.
     */
    public function create(User $user, Listing $listing): bool
    {
        if ($user->role !== Role::Tourist || ! $listing->isPublished()) {
            return false;
        }

        if ($listing->reviews()->where('user_id', $user->id)->exists()) {
            return false;
        }

        return $user->bookings()->where('listing_id', $listing->id)->where('status', BookingStatus::Completed)->exists()
            || SiteVisit::where('listing_id', $listing->id)->where('user_id', $user->id)->exists();
    }

    public function update(User $user, Review $review): bool
    {
        return $review->user_id === $user->id;
    }

    public function delete(User $user, Review $review): bool
    {
        return $review->user_id === $user->id || $user->hasRole(Role::Admin);
    }

    public function reply(User $user, Review $review): bool
    {
        return $review->listing?->business?->owner_id === $user->id;
    }

    public function moderate(User $user): bool
    {
        return $user->hasRole(Role::TourismOfficer, Role::Admin);
    }
}

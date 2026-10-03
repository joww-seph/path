<?php

namespace App\Policies;

use App\Enums\ListingStatus;
use App\Enums\Role;
use App\Models\Listing;
use App\Models\User;

class ListingPolicy
{
    /**
     * Published listings are public; drafts are visible to their partner and the tourism office.
     */
    public function view(?User $user, Listing $listing): bool
    {
        return $listing->isPublished() || ($user !== null && $this->update($user, $listing));
    }

    public function create(User $user): bool
    {
        return $user->hasRole(Role::Partner, Role::TourismOfficer, Role::Admin);
    }

    public function update(User $user, Listing $listing): bool
    {
        return $this->isOffice($user) || $this->ownsListing($user, $listing);
    }

    /**
     * Partners can delete listings that were never published; the office can delete any listing.
     */
    public function delete(User $user, Listing $listing): bool
    {
        if ($this->isOffice($user)) {
            return true;
        }

        return $this->ownsListing($user, $listing)
            && in_array($listing->status, [ListingStatus::Draft, ListingStatus::Rejected], true);
    }

    /**
     * A verified partner sends a draft (or a listing sent back for changes) to the office.
     */
    public function submit(User $user, Listing $listing): bool
    {
        return $this->ownsListing($user, $listing)
            && $listing->business?->isApproved() === true
            && in_array($listing->status, [ListingStatus::Draft, ListingStatus::Rejected], true);
    }

    public function review(User $user, Listing $listing): bool
    {
        return $this->isOffice($user) && $listing->status === ListingStatus::Pending;
    }

    private function isOffice(User $user): bool
    {
        return $user->hasRole(Role::TourismOfficer, Role::Admin);
    }

    private function ownsListing(User $user, Listing $listing): bool
    {
        return $listing->business_id !== null && $listing->business?->owner_id === $user->id;
    }
}

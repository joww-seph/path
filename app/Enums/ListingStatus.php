<?php

namespace App\Enums;

enum ListingStatus: string
{
    case Draft = 'draft';
    case Pending = 'pending';
    case Published = 'published';
    case Rejected = 'rejected';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Pending => 'Awaiting approval',
            self::Published => 'Published',
            self::Rejected => 'Changes requested',
            self::Archived => 'Archived',
        };
    }
}

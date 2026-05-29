<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Catalog\Enums;

enum AttendeeListVisibility: string
{
    case Private = 'private';
    case OrganizerOnly = 'organizer_only';
    case AttendeesOnly = 'attendees_only';
    case Public = 'public';

    public function rank(): int
    {
        return match ($this) {
            self::Private => 0,
            self::OrganizerOnly => 1,
            self::AttendeesOnly => 2,
            self::Public => 3,
        };
    }

    public function isMoreRestrictiveThan(self $other): bool
    {
        return $this->rank() < $other->rank();
    }
}

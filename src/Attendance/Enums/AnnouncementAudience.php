<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Attendance\Enums;

enum AnnouncementAudience: string
{
    case All = 'all';
    case Registered = 'registered';
    case CheckedIn = 'checked_in';
    case ByTicketType = 'by_ticket_type';
    case BySession = 'by_session';
}

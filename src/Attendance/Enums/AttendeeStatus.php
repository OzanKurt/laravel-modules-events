<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Attendance\Enums;

enum AttendeeStatus: string
{
    case Registered = 'registered';
    case Cancelled = 'cancelled';
    case CheckedIn = 'checked_in';
    case NoShow = 'no_show';
}

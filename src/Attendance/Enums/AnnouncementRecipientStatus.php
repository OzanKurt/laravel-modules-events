<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Attendance\Enums;

enum AnnouncementRecipientStatus: string
{
    case Pending = 'pending';
    case Sent = 'sent';
    case Failed = 'failed';
    case Opened = 'opened';
}

<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Ticketing\Enums;

enum TicketStatus: string
{
    case Issued = 'issued';
    case Cancelled = 'cancelled';
    case Refunded = 'refunded';
    case CheckedIn = 'checked_in';
    case Transferred = 'transferred';
}

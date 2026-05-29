<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Ticketing\Enums;

enum TicketTypeMode: string
{
    case Open = 'open';
    case Application = 'application';
    case Rsvp = 'rsvp';
}

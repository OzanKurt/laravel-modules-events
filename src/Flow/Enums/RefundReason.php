<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Flow\Enums;

enum RefundReason: string
{
    case Rejection = 'rejection';
    case CancelledEvent = 'cancelled_event';
    case AttendeeRequest = 'attendee_request';
    case OrganizerInitiated = 'organizer_initiated';
    case Other = 'other';
}

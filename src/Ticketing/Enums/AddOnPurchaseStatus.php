<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Ticketing\Enums;

enum AddOnPurchaseStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Cancelled = 'cancelled';
    case Refunded = 'refunded';
}

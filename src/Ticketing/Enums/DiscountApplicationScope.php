<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Ticketing\Enums;

enum DiscountApplicationScope: string
{
    case Order = 'order';
    case PerTicket = 'per_ticket';
}

<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Ticketing\Enums;

enum DiscountKind: string
{
    case Percent = 'percent';
    case FlatAmount = 'flat_amount';
}

<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Flow\Enums;

enum PayoutStatus: string
{
    case Accrued = 'accrued';
    case PaidOut = 'paid_out';
    case Reversed = 'reversed';
}

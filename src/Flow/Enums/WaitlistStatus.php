<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Flow\Enums;

enum WaitlistStatus: string
{
    case Waiting = 'waiting';
    case Offered = 'offered';
    case Claimed = 'claimed';
    case Expired = 'expired';
}

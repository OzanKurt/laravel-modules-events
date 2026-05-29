<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Flow\Enums;

enum SponsorStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Cancelled = 'cancelled';
}

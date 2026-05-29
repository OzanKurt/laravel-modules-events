<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Flow\Enums;

enum QueueStatus: string
{
    case Waiting = 'waiting';
    case Active = 'active';
    case Expired = 'expired';
    case Completed = 'completed';
    case Abandoned = 'abandoned';
}

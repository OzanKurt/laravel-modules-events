<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Flow\Enums;

enum RefundStatus: string
{
    case Pending = 'pending';
    case Processed = 'processed';
    case Failed = 'failed';
}

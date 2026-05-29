<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Catalog\Enums;

enum EventStatus: string
{
    case Draft = 'draft';
    case PendingApproval = 'pending_approval';
    case Published = 'published';
    case Cancelled = 'cancelled';
    case Completed = 'completed';
}

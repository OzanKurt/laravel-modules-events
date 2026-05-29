<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Catalog\Enums;

enum RecurrenceFrequency: string
{
    case None = 'none';
    case Daily = 'daily';
    case Weekly = 'weekly';
    case Monthly = 'monthly';
    case Yearly = 'yearly';
}

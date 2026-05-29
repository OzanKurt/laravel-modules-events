<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Eligibility\Enums;

enum CheckStatus: string
{
    case Pending = 'pending';
    case Passed = 'passed';
    case Failed = 'failed';
    case Waived = 'waived';
}

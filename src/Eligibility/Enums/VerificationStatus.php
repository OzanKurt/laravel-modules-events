<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Eligibility\Enums;

enum VerificationStatus: string
{
    case Pending = 'pending';
    case Verified = 'verified';
    case Rejected = 'rejected';
}

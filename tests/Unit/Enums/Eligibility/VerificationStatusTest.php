<?php

declare(strict_types=1);

use Kurt\Modules\Events\Eligibility\Enums\VerificationStatus;

it('has expected cases and values', function () {
    expect(VerificationStatus::Pending->value)->toBe('pending');
    expect(VerificationStatus::Verified->value)->toBe('verified');
    expect(VerificationStatus::Rejected->value)->toBe('rejected');
});

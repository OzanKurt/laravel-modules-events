<?php

declare(strict_types=1);

use Kurt\Modules\Events\Attendance\Enums\ApplicationStatus;

it('has expected cases and values', function () {
    expect(ApplicationStatus::Pending->value)->toBe('pending');
    expect(ApplicationStatus::Approved->value)->toBe('approved');
    expect(ApplicationStatus::Rejected->value)->toBe('rejected');
    expect(ApplicationStatus::Withdrawn->value)->toBe('withdrawn');
    expect(ApplicationStatus::Expired->value)->toBe('expired');
});

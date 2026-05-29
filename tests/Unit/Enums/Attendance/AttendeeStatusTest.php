<?php

declare(strict_types=1);

use Kurt\Modules\Events\Attendance\Enums\AttendeeStatus;

it('has expected cases and values', function () {
    expect(AttendeeStatus::Registered->value)->toBe('registered');
    expect(AttendeeStatus::Cancelled->value)->toBe('cancelled');
    expect(AttendeeStatus::CheckedIn->value)->toBe('checked_in');
    expect(AttendeeStatus::NoShow->value)->toBe('no_show');
});

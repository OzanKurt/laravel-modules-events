<?php

declare(strict_types=1);

use Kurt\Modules\Events\Attendance\Enums\AnnouncementRecipientStatus;

it('has expected cases and values', function () {
    expect(AnnouncementRecipientStatus::Pending->value)->toBe('pending');
    expect(AnnouncementRecipientStatus::Sent->value)->toBe('sent');
    expect(AnnouncementRecipientStatus::Failed->value)->toBe('failed');
    expect(AnnouncementRecipientStatus::Opened->value)->toBe('opened');
});

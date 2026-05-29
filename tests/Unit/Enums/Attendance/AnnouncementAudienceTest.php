<?php

declare(strict_types=1);

use Kurt\Modules\Events\Attendance\Enums\AnnouncementAudience;

it('has expected cases and values', function () {
    expect(AnnouncementAudience::All->value)->toBe('all');
    expect(AnnouncementAudience::Registered->value)->toBe('registered');
    expect(AnnouncementAudience::CheckedIn->value)->toBe('checked_in');
    expect(AnnouncementAudience::ByTicketType->value)->toBe('by_ticket_type');
    expect(AnnouncementAudience::BySession->value)->toBe('by_session');
});

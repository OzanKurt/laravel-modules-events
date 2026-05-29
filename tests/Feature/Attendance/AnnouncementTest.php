<?php

declare(strict_types=1);

use Kurt\Modules\Events\Attendance\Enums\AnnouncementAudience;
use Kurt\Modules\Events\Attendance\Enums\AnnouncementRecipientStatus;
use Kurt\Modules\Events\Attendance\Models\Announcement;
use Kurt\Modules\Events\Attendance\Models\AnnouncementRecipient;

it('persists audience + audience_filter', function () {
    $ann = Announcement::factory()->create([
        'audience' => AnnouncementAudience::ByTicketType,
        'audience_filter' => ['ticket_type_ids' => [1, 2]],
    ]);

    expect($ann->audience)->toBe(AnnouncementAudience::ByTicketType);
    expect($ann->audience_filter)->toBe(['ticket_type_ids' => [1, 2]]);
});

it('has many recipients', function () {
    $ann = Announcement::factory()->create();
    AnnouncementRecipient::factory()->create([
        'announcement_id' => $ann->id,
        'status' => AnnouncementRecipientStatus::Sent,
    ]);

    expect($ann->recipients()->count())->toBe(1);
});

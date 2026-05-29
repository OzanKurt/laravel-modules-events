<?php

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Kurt\Modules\Events\Attendance\Enums\AnnouncementAudience;
use Kurt\Modules\Events\Attendance\Enums\AttendeeStatus;
use Kurt\Modules\Events\Attendance\Models\Announcement;
use Kurt\Modules\Events\Attendance\Models\Attendee;
use Kurt\Modules\Events\Attendance\Support\AnnouncementDispatcher;
use Kurt\Modules\Events\Catalog\Models\Event as CatalogEvent;
use Kurt\Modules\Events\Console\Commands\DispatchAnnouncementsCommand;

beforeEach(function () {
    app(Kernel::class)->registerCommand(new DispatchAnnouncementsCommand);
});

it('dispatches announcements whose scheduled_for time has passed', function () {
    $catalogEvent = CatalogEvent::factory()->create();
    Attendee::factory()->create([
        'event_id' => $catalogEvent->id,
        'user_id' => 1,
        'status' => AttendeeStatus::Registered,
    ]);
    $announcement = Announcement::factory()->create([
        'event_id' => $catalogEvent->id,
        'audience' => AnnouncementAudience::All,
        'scheduled_for' => now()->subMinute(),
        'sent_at' => null,
    ]);

    $this->app->instance(AnnouncementDispatcher::class, new AnnouncementDispatcher(app('config')));
    $exit = Artisan::call('events:dispatch-announcements');

    expect($exit)->toBe(0);

    $announcement->refresh();
    expect($announcement->sent_at)->not->toBeNull();
});

it('skips future-scheduled announcements', function () {
    $catalogEvent = CatalogEvent::factory()->create();
    Attendee::factory()->create([
        'event_id' => $catalogEvent->id,
        'user_id' => 1,
        'status' => AttendeeStatus::Registered,
    ]);
    $announcement = Announcement::factory()->create([
        'event_id' => $catalogEvent->id,
        'audience' => AnnouncementAudience::All,
        'scheduled_for' => now()->addMinutes(10),
        'sent_at' => null,
    ]);

    $this->app->instance(AnnouncementDispatcher::class, new AnnouncementDispatcher(app('config')));
    Artisan::call('events:dispatch-announcements');

    $announcement->refresh();
    expect($announcement->sent_at)->toBeNull();
});

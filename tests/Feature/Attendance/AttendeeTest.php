<?php

declare(strict_types=1);

use Kurt\Modules\Events\Attendance\Models\Attendee;
use Kurt\Modules\Events\Catalog\Enums\AttendeeListVisibility;
use Kurt\Modules\Events\Catalog\Models\Event;
use Kurt\Modules\Events\Tests\Stubs\StubUser;

it('persists profile as json', function () {
    $user = StubUser::create(['email' => 'a@b.c']);
    $event = Event::factory()->create();
    $attendee = Attendee::factory()->create([
        'event_id' => $event->id,
        'user_id' => $user->id,
        'profile' => ['name' => 'Alice', 'date_of_birth' => '1990-01-01'],
    ]);

    expect($attendee->profile)->toBeArray();
    expect($attendee->profile['name'])->toBe('Alice');
});

it('listVisibility uses event level when more restrictive', function () {
    $user = StubUser::create(['email' => 'b@b.c']);
    $event = Event::factory()->create(['attendee_list_visibility' => AttendeeListVisibility::OrganizerOnly]);
    $attendee = Attendee::factory()->create([
        'event_id' => $event->id,
        'user_id' => $user->id,
        'profile' => ['list_visibility' => 'public'],
    ]);

    expect($attendee->listVisibility())->toBe(AttendeeListVisibility::OrganizerOnly);
});

it('listVisibility falls back to self when event is permissive', function () {
    $user = StubUser::create(['email' => 'c@b.c']);
    $event = Event::factory()->create(['attendee_list_visibility' => AttendeeListVisibility::Public]);
    $attendee = Attendee::factory()->create([
        'event_id' => $event->id,
        'user_id' => $user->id,
        'profile' => ['list_visibility' => 'private'],
    ]);

    expect($attendee->listVisibility())->toBe(AttendeeListVisibility::Private);
});

it('listVisibility defaults public when no profile setting', function () {
    $user = StubUser::create(['email' => 'd@b.c']);
    $event = Event::factory()->create(['attendee_list_visibility' => AttendeeListVisibility::Public]);
    $attendee = Attendee::factory()->create([
        'event_id' => $event->id,
        'user_id' => $user->id,
        'profile' => ['name' => 'No vis key'],
    ]);

    expect($attendee->listVisibility())->toBe(AttendeeListVisibility::Public);
});

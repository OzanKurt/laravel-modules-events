<?php

declare(strict_types=1);

use Kurt\Modules\Events\Catalog\Enums\OrganizerRole;
use Kurt\Modules\Events\Catalog\Models\Event;
use Kurt\Modules\Events\Catalog\Models\EventOrganizer;
use Kurt\Modules\Events\Tests\Stubs\StubUser;

it('creates organizer pivot row', function () {
    $user = StubUser::create(['email' => 'org@example.com']);
    $event = Event::factory()->create();

    $organizer = EventOrganizer::create([
        'event_id' => $event->id,
        'user_id' => $user->id,
        'role' => OrganizerRole::Owner,
        'commission_basis_points' => 5000,
    ]);

    expect($organizer->role)->toBe(OrganizerRole::Owner);
    expect($organizer->commission_basis_points)->toBe(5000);
    expect($organizer->event->id)->toBe($event->id);
});

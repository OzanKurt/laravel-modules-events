<?php

declare(strict_types=1);

use Kurt\Modules\Events\Attendance\Models\Attendee;
use Kurt\Modules\Events\Catalog\Enums\EventStatus;
use Kurt\Modules\Events\Catalog\Enums\EventVisibility;
use Kurt\Modules\Events\Catalog\Enums\OrganizerRole;
use Kurt\Modules\Events\Catalog\Models\Event;
use Kurt\Modules\Events\Tests\Stubs\StubUser;

it('lists only published public events with sorting', function () {
    $soon = Event::factory()->published()->create([
        'visibility' => EventVisibility::Public,
        'starts_at' => now()->addDays(2),
        'ends_at' => now()->addDays(2)->addHour(),
    ]);
    $later = Event::factory()->published()->create([
        'visibility' => EventVisibility::Public,
        'starts_at' => now()->addDays(20),
        'ends_at' => now()->addDays(20)->addHour(),
    ]);
    // Should be excluded: a draft and a private published event.
    Event::factory()->create(['status' => EventStatus::Draft, 'visibility' => EventVisibility::Public]);
    Event::factory()->published()->create(['visibility' => EventVisibility::Private]);

    $response = $this->getJson('/api/events?sort=starts_at');

    $response->assertOk();
    $ids = array_column($response->json('data'), 'id');
    expect($ids)->toBe([$soon->id, $later->id])
        ->and($response->json('meta.pagination.total'))->toBe(2);
});

it('shows a single public event', function () {
    $event = Event::factory()->published()->create(['visibility' => EventVisibility::Public]);

    $this->getJson("/api/events/{$event->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $event->id)
        ->assertJsonPath('data.status', 'published');
});

it('hides a private event from a guest on show (403)', function () {
    $event = Event::factory()->create([
        'status' => EventStatus::Draft,
        'visibility' => EventVisibility::Private,
    ]);

    $this->getJson("/api/events/{$event->id}")->assertForbidden();
});

it('blocks a guest from creating an event (401)', function () {
    $this->postJson('/api/events', [
        'title' => 'X',
        'starts_at' => now()->addDay()->toIso8601String(),
        'ends_at' => now()->addDay()->addHour()->toIso8601String(),
        'timezone' => 'UTC',
    ])->assertUnauthorized();
});

it('lets an authenticated user create an event and become its owner', function () {
    $user = StubUser::create(['email' => 'creator@x.com']);

    $response = $this->actingAs($user)->postJson('/api/events', [
        'title' => 'My Conference',
        'starts_at' => now()->addDays(10)->toIso8601String(),
        'ends_at' => now()->addDays(10)->addHours(3)->toIso8601String(),
        'timezone' => 'UTC',
        'visibility' => 'public',
    ]);

    $response->assertCreated()->assertJsonPath('data.title', 'My Conference');
    $event = Event::findOrFail($response->json('data.id'));
    expect($event->organizers()->where('user_id', $user->id)->where('role', OrganizerRole::Owner->value)->exists())->toBeTrue();
});

it('validates event creation input (422)', function () {
    $user = StubUser::create(['email' => 'creator2@x.com']);

    $this->actingAs($user)->postJson('/api/events', ['title' => ''])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['title', 'starts_at', 'ends_at', 'timezone']);
});

it('allows an organizer to update but forbids a stranger (403)', function () {
    $event = Event::factory()->published()->create();
    $owner = organizerOf($event);
    $stranger = StubUser::create(['email' => 'stranger@x.com']);

    $this->actingAs($owner)->patchJson("/api/events/{$event->id}", ['title' => 'Renamed'])
        ->assertOk()
        ->assertJsonPath('data.title', 'Renamed');

    $this->actingAs($stranger)->patchJson("/api/events/{$event->id}", ['title' => 'Nope'])
        ->assertForbidden();
});

it('only lets the owner delete an event', function () {
    $event = Event::factory()->create();
    $owner = organizerOf($event, OrganizerRole::Owner);
    $manager = organizerOf($event, OrganizerRole::Manager);

    $this->actingAs($manager)->deleteJson("/api/events/{$event->id}")->assertForbidden();
    $this->actingAs($owner)->deleteJson("/api/events/{$event->id}")->assertNoContent();
    expect(Event::find($event->id))->toBeNull();
});

it('publishes and cancels an event as organizer', function () {
    $event = Event::factory()->create(['status' => EventStatus::Draft]);
    $owner = organizerOf($event);

    $this->actingAs($owner)->postJson("/api/events/{$event->id}/publish")
        ->assertOk()
        ->assertJsonPath('data.status', 'published');

    $this->actingAs($owner)->postJson("/api/events/{$event->id}/cancel", ['reason' => 'weather'])
        ->assertOk()
        ->assertJsonPath('data.status', 'cancelled');

    expect($event->refresh()->cancellation_reason)->toBe('weather');
});

it('forbids a stranger from publishing (403)', function () {
    $event = Event::factory()->create();
    organizerOf($event);
    $stranger = StubUser::create(['email' => 'nope@x.com']);

    $this->actingAs($stranger)->postJson("/api/events/{$event->id}/publish")->assertForbidden();
});

it('lets an organizer list attendees but forbids a stranger (403) and a guest (401)', function () {
    $event = Event::factory()->published()->create();
    $owner = organizerOf($event);
    $stranger = StubUser::create(['email' => 'roster-stranger@x.com']);
    Attendee::factory()->count(2)
        ->sequence(['user_id' => 501], ['user_id' => 502])
        ->create(['event_id' => $event->id]);

    $this->getJson("/api/events/{$event->id}/attendees")->assertUnauthorized();
    $this->actingAs($stranger)->getJson("/api/events/{$event->id}/attendees")->assertForbidden();

    $this->actingAs($owner)->getJson("/api/events/{$event->id}/attendees")
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('meta.pagination.total', 2);
});

<?php

declare(strict_types=1);

use Kurt\Modules\Events\Catalog\Enums\OrganizerRole;
use Kurt\Modules\Events\Catalog\Models\Event;
use Kurt\Modules\Events\Tests\Stubs\StubUser;
use Kurt\Modules\Events\Ticketing\Models\TicketType;

it('lists ticket types for an event publicly', function () {
    $event = Event::factory()->published()->create();
    TicketType::factory()->for($event)->create(['name' => ['en' => 'General'], 'position' => 0]);
    TicketType::factory()->for($event)->create(['name' => ['en' => 'VIP'], 'position' => 1]);

    $this->getJson("/api/events/{$event->id}/ticket-types?sort=position")
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('meta.pagination.total', 2);
});

it('lets an organizer create a ticket type but forbids a stranger (403)', function () {
    $event = Event::factory()->create();
    $owner = organizerOf($event, OrganizerRole::Manager);
    $stranger = StubUser::create(['email' => 'stranger-tt@x.com']);

    $this->actingAs($owner)->postJson("/api/events/{$event->id}/ticket-types", [
        'name' => 'Early Bird',
        'price_minor' => 2500,
        'currency' => 'USD',
        'capacity' => 100,
    ])->assertCreated()->assertJsonPath('data.name', 'Early Bird');

    $this->actingAs($stranger)->postJson("/api/events/{$event->id}/ticket-types", [
        'name' => 'Nope',
        'price_minor' => 100,
    ])->assertForbidden();
});

it('blocks a guest from creating a ticket type (401)', function () {
    $event = Event::factory()->create();

    $this->postJson("/api/events/{$event->id}/ticket-types", [
        'name' => 'X',
        'price_minor' => 100,
    ])->assertUnauthorized();
});

it('updates and deletes a ticket type as organizer', function () {
    $event = Event::factory()->create();
    $owner = organizerOf($event);
    $type = TicketType::factory()->for($event)->create(['price_minor' => 1000]);

    $this->actingAs($owner)->patchJson("/api/events/ticket-types/{$type->id}", ['price_minor' => 4200])
        ->assertOk()
        ->assertJsonPath('data.price_minor', 4200);

    $this->actingAs($owner)->deleteJson("/api/events/ticket-types/{$type->id}")->assertNoContent();
    expect(TicketType::find($type->id))->toBeNull();
});

it('forbids a stranger from updating a ticket type (403)', function () {
    $event = Event::factory()->create();
    organizerOf($event);
    $stranger = StubUser::create(['email' => 'stranger-upd@x.com']);
    $type = TicketType::factory()->for($event)->create();

    $this->actingAs($stranger)->patchJson("/api/events/ticket-types/{$type->id}", ['price_minor' => 1])
        ->assertForbidden();
});

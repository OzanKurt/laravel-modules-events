<?php

declare(strict_types=1);

use Kurt\Modules\Events\Catalog\Enums\OrganizerRole;
use Kurt\Modules\Events\Catalog\Models\Event;
use Kurt\Modules\Events\Support\Events as EventsService;
use Kurt\Modules\Events\Tests\Stubs\StubUser;
use Kurt\Modules\Events\Ticketing\Enums\TicketStatus;
use Kurt\Modules\Events\Ticketing\Models\Ticket;
use Kurt\Modules\Events\Ticketing\Models\TicketType;

it('registers for a free ticket type and issues a ticket (happy path)', function () {
    $event = Event::factory()->published()->create();
    $type = TicketType::factory()->for($event)->rsvp()->create(['capacity' => 10]);
    $buyer = StubUser::create(['email' => 'buyer@x.com', 'name' => 'Buyer']);

    $response = $this->actingAs($buyer)->postJson("/api/events/ticket-types/{$type->id}/register");

    $response->assertCreated()
        ->assertJsonPath('data.status', 'paid')
        ->assertJsonCount(1, 'data.tickets');

    $ticket = Ticket::where('event_id', $event->id)->firstOrFail();
    expect($ticket->status)->toBe(TicketStatus::Issued)
        ->and($ticket->holder_id)->toBe($buyer->id)
        ->and($type->refresh()->sold_count)->toBe(1);
});

it('returns a clean 409 when the ticket type is sold out (limiter, not bypass)', function () {
    $event = Event::factory()->published()->create();
    $type = TicketType::factory()->for($event)->rsvp()->create(['capacity' => 1]);
    $first = StubUser::create(['email' => 'first@x.com']);
    $second = StubUser::create(['email' => 'second@x.com']);

    $this->actingAs($first)->postJson("/api/events/ticket-types/{$type->id}/register")->assertCreated();

    $this->actingAs($second)->postJson("/api/events/ticket-types/{$type->id}/register")
        ->assertStatus(409);

    // The limiter held: capacity was never oversold.
    expect($type->refresh()->sold_count)->toBe(1)
        ->and(Ticket::where('event_id', $event->id)->count())->toBe(1);
});

it('blocks a guest from registering (401)', function () {
    $event = Event::factory()->published()->create();
    $type = TicketType::factory()->for($event)->rsvp()->create();

    $this->postJson("/api/events/ticket-types/{$type->id}/register")->assertUnauthorized();
});

it("lists the authenticated user's registrations", function () {
    $event = Event::factory()->published()->create();
    $type = TicketType::factory()->for($event)->rsvp()->create(['capacity' => 5]);
    $buyer = StubUser::create(['email' => 'me@x.com', 'name' => 'Me']);

    $this->actingAs($buyer)->postJson("/api/events/ticket-types/{$type->id}/register")->assertCreated();

    $this->actingAs($buyer)->getJson('/api/events/registrations')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.event_id', $event->id);
});

it('lets a buyer cancel a paid order within the refund window', function () {
    $event = Event::factory()->published()->create();
    $type = TicketType::factory()->for($event)->create(['price_minor' => 5000, 'capacity' => 10]);
    $buyer = StubUser::create(['email' => 'cancel-in@x.com', 'name' => 'Buyer']);

    $events = app(EventsService::class);
    $order = $events->reserve($type, $buyer, 1, [['name' => 'Buyer', 'email' => 'cancel-in@x.com', 'user_id' => $buyer->id]]);
    $events->pay($order, 'stripe', 'ch_1');

    $this->actingAs($buyer)->postJson("/api/events/orders/{$order->id}/cancel")
        ->assertOk()
        ->assertJsonPath('data.order_id', $order->id)
        ->assertJsonPath('data.status', 'pending');
});

it('rejects a buyer cancel outside the refund/cancel window (422)', function () {
    config()->set('events.refunds.consumer_protection_window_days', 0);

    $event = Event::factory()->published()->create();
    // No self-cancel deadline and the EU window disabled => cancellation not permitted.
    $type = TicketType::factory()->for($event)->create([
        'price_minor' => 5000,
        'capacity' => 10,
        'self_cancel_deadline_hours_before_event' => null,
    ]);
    $buyer = StubUser::create(['email' => 'cancel-out@x.com', 'name' => 'Buyer']);

    $events = app(EventsService::class);
    $order = $events->reserve($type, $buyer, 1, [['name' => 'Buyer', 'email' => 'cancel-out@x.com', 'user_id' => $buyer->id]]);
    $events->pay($order, 'stripe', 'ch_2');

    $this->actingAs($buyer)->postJson("/api/events/orders/{$order->id}/cancel")->assertStatus(422);
});

it('checks a ticket in, blocks replay (409) and strangers (403)', function () {
    $event = Event::factory()->published()->create();
    $type = TicketType::factory()->for($event)->rsvp()->create(['capacity' => 5]);
    $buyer = StubUser::create(['email' => 'attendee@x.com', 'name' => 'Attendee']);
    $scanner = organizerOf($event, OrganizerRole::Scanner);
    $stranger = StubUser::create(['email' => 'stranger-scan@x.com']);

    $this->actingAs($buyer)->postJson("/api/events/ticket-types/{$type->id}/register")->assertCreated();
    $ticket = Ticket::where('event_id', $event->id)->firstOrFail();

    // Stranger cannot check in.
    $this->actingAs($stranger)->postJson("/api/events/tickets/{$ticket->id}/check-in")->assertForbidden();

    // Scanner checks in.
    $this->actingAs($scanner)->postJson("/api/events/tickets/{$ticket->id}/check-in")
        ->assertOk()
        ->assertJsonPath('data.status', 'checked_in');

    // Replay is rejected by the domain's replay protection.
    $this->actingAs($scanner)->postJson("/api/events/tickets/{$ticket->id}/check-in")->assertStatus(409);

    expect($ticket->refresh()->status)->toBe(TicketStatus::CheckedIn);
});

it('blocks a guest from checking in (401)', function () {
    // Set the ticket up through the domain service so no actingAs() lingers on
    // the guard for the guest request that follows.
    $event = Event::factory()->published()->create();
    $type = TicketType::factory()->for($event)->rsvp()->create(['capacity' => 5]);
    $buyer = StubUser::create(['email' => 'a2@x.com', 'name' => 'A2']);

    $events = app(EventsService::class);
    $order = $events->reserve($type, $buyer, 1, [['name' => 'A2', 'email' => 'a2@x.com', 'user_id' => $buyer->id]]);
    $events->pay($order, 'free', 'free-'.$order->id);
    $ticket = Ticket::where('event_id', $event->id)->firstOrFail();

    $this->postJson("/api/events/tickets/{$ticket->id}/check-in")->assertUnauthorized();
});

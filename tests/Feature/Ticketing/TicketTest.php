<?php

declare(strict_types=1);

use Kurt\Modules\Events\Catalog\Models\Event;
use Kurt\Modules\Events\Ticketing\Enums\TicketStatus;
use Kurt\Modules\Events\Ticketing\Models\Ticket;
use Kurt\Modules\Events\Ticketing\Models\TicketType;

it('isCheckedIn returns true only when status matches', function () {
    $issued = Ticket::factory()->create();
    $checked = Ticket::factory()->checkedIn()->create();

    expect($issued->isCheckedIn())->toBeFalse();
    expect($checked->isCheckedIn())->toBeTrue();
});

it('transferable returns false when ticket type disallows it', function () {
    $event = Event::factory()->upcoming()->create();
    $type = TicketType::factory()->nontransferable()->create(['event_id' => $event->id]);
    $ticket = Ticket::factory()->create(['ticket_type_id' => $type->id, 'event_id' => $event->id]);

    expect($ticket->transferable())->toBeFalse();
});

it('transferable returns false past transfer deadline', function () {
    $event = Event::factory()->create([
        'starts_at' => now()->addHour(),
        'ends_at' => now()->addHours(2),
    ]);
    $type = TicketType::factory()->create([
        'event_id' => $event->id,
        'transferable' => true,
        'transfer_deadline_hours_before_event' => 24,
    ]);
    $ticket = Ticket::factory()->create([
        'event_id' => $event->id,
        'ticket_type_id' => $type->id,
    ]);

    expect($ticket->transferable())->toBeFalse();
});

it('transferable returns true within deadline', function () {
    $event = Event::factory()->create([
        'starts_at' => now()->addDays(10),
        'ends_at' => now()->addDays(10)->addHours(2),
    ]);
    $type = TicketType::factory()->create([
        'event_id' => $event->id,
        'transferable' => true,
        'transfer_deadline_hours_before_event' => 24,
    ]);
    $ticket = Ticket::factory()->create([
        'event_id' => $event->id,
        'ticket_type_id' => $type->id,
    ]);

    expect($ticket->transferable())->toBeTrue();
});

it('casts status to TicketStatus enum', function () {
    $ticket = Ticket::factory()->create(['status' => TicketStatus::Issued]);

    expect($ticket->status)->toBe(TicketStatus::Issued);
});

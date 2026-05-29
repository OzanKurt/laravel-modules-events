<?php

declare(strict_types=1);

use Kurt\Modules\Events\Catalog\Models\Event;
use Kurt\Modules\Events\Eligibility\Enums\CheckStatus;
use Kurt\Modules\Events\Eligibility\Enums\RequirementType;
use Kurt\Modules\Events\Eligibility\Models\Requirement;
use Kurt\Modules\Events\Eligibility\Models\RequirementCheck;
use Kurt\Modules\Events\Ticketing\Models\TicketType;

it('creates an event-scoped requirement with payload', function () {
    $event = Event::factory()->create();
    $req = Requirement::factory()->create([
        'event_id' => $event->id,
        'ticket_type_id' => null,
        'type' => RequirementType::AgeMin,
        'payload' => ['min' => 18],
        'strict' => true,
    ]);

    expect($req->type)->toBe(RequirementType::AgeMin);
    expect($req->payload['min'])->toBe(18);
    expect($req->strict)->toBeTrue();
    expect($req->event?->id)->toBe($event->id);
});

it('creates a ticket-type-scoped requirement', function () {
    $type = TicketType::factory()->create();
    $req = Requirement::factory()->create([
        'event_id' => null,
        'ticket_type_id' => $type->id,
        'type' => RequirementType::Document,
        'payload' => ['accepted_kinds' => ['id_card', 'passport']],
    ]);

    expect($req->ticketType?->id)->toBe($type->id);
    expect($req->type)->toBe(RequirementType::Document);
});

it('hasMany checks', function () {
    $req = Requirement::factory()->create();
    RequirementCheck::factory()->create(['requirement_id' => $req->id, 'status' => CheckStatus::Passed]);

    expect($req->checks()->count())->toBe(1);
});

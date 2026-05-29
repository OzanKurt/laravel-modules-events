<?php

declare(strict_types=1);

use Kurt\Modules\Events\Attendance\Models\Attendee;
use Kurt\Modules\Events\Catalog\Models\Event;
use Kurt\Modules\Events\Eligibility\Engine\RequirementEngine;
use Kurt\Modules\Events\Eligibility\Enums\CheckStatus;
use Kurt\Modules\Events\Eligibility\Enums\RequirementType;
use Kurt\Modules\Events\Eligibility\Evaluators\AgeMinEvaluator;
use Kurt\Modules\Events\Eligibility\Models\Requirement;
use Kurt\Modules\Events\Eligibility\Models\RequirementCheck;
use Kurt\Modules\Events\Tests\Stubs\StubUser;
use Kurt\Modules\Events\Ticketing\Models\TicketType;

beforeEach(function () {
    config()->set('events.requirements.evaluators.age_min', AgeMinEvaluator::class);
});

it('evaluates ticket-type requirement and persists check', function () {
    $user = StubUser::create(['email' => 'engine-pass@example.com']);
    $event = Event::factory()->create();
    $type = TicketType::factory()->create(['event_id' => $event->id]);
    Requirement::factory()->create([
        'event_id' => null,
        'ticket_type_id' => $type->id,
        'type' => RequirementType::AgeMin,
        'payload' => ['min' => 18],
        'strict' => true,
    ]);

    $attendee = Attendee::factory()->create([
        'event_id' => $event->id,
        'user_id' => $user->id,
        'profile' => ['date_of_birth' => now()->subYears(25)->toDateString()],
    ]);

    $outcome = app(RequirementEngine::class)->evaluateFor($attendee, $type);

    expect($outcome->allPassed)->toBeTrue();
    expect($outcome->anyStrictFailed)->toBeFalse();
    expect($outcome->checks)->toHaveCount(1);
    expect(RequirementCheck::query()->where('attendee_id', $attendee->id)->count())->toBe(1);
});

it('flags allPassed=false when any check fails', function () {
    $user = StubUser::create(['email' => 'engine-failed@example.com']);
    $event = Event::factory()->create();
    $type = TicketType::factory()->create(['event_id' => $event->id]);
    Requirement::factory()->create([
        'event_id' => null,
        'ticket_type_id' => $type->id,
        'type' => RequirementType::AgeMin,
        'payload' => ['min' => 18],
        'strict' => false,
    ]);

    $attendee = Attendee::factory()->create([
        'event_id' => $event->id,
        'user_id' => $user->id,
        'profile' => ['date_of_birth' => now()->subYears(16)->toDateString()],
    ]);

    $outcome = app(RequirementEngine::class)->evaluateFor($attendee, $type);

    expect($outcome->allPassed)->toBeFalse();
    expect($outcome->anyStrictFailed)->toBeFalse();
    $check = RequirementCheck::query()->where('attendee_id', $attendee->id)->firstOrFail();
    expect($check->status)->toBe(CheckStatus::Failed);
});

it('flags anyStrictFailed=true when a strict requirement fails', function () {
    $user = StubUser::create(['email' => 'engine-strict@example.com']);
    $event = Event::factory()->create();
    $type = TicketType::factory()->create(['event_id' => $event->id]);
    Requirement::factory()->create([
        'event_id' => null,
        'ticket_type_id' => $type->id,
        'type' => RequirementType::AgeMin,
        'payload' => ['min' => 18],
        'strict' => true,
    ]);

    $attendee = Attendee::factory()->create([
        'event_id' => $event->id,
        'user_id' => $user->id,
        'profile' => ['date_of_birth' => now()->subYears(16)->toDateString()],
    ]);

    $outcome = app(RequirementEngine::class)->evaluateFor($attendee, $type);

    expect($outcome->allPassed)->toBeFalse();
    expect($outcome->anyStrictFailed)->toBeTrue();
});

it('is idempotent — updates instead of duplicating checks on re-run', function () {
    $user = StubUser::create(['email' => 'engine-idempotent@example.com']);
    $event = Event::factory()->create();
    $type = TicketType::factory()->create(['event_id' => $event->id]);
    Requirement::factory()->create([
        'event_id' => null,
        'ticket_type_id' => $type->id,
        'type' => RequirementType::AgeMin,
        'payload' => ['min' => 18],
        'strict' => true,
    ]);

    $attendee = Attendee::factory()->create([
        'event_id' => $event->id,
        'user_id' => $user->id,
        'profile' => ['date_of_birth' => now()->subYears(25)->toDateString()],
    ]);

    $engine = app(RequirementEngine::class);
    $engine->evaluateFor($attendee, $type);
    $engine->evaluateFor($attendee, $type);
    $engine->evaluateFor($attendee, $type);

    expect(RequirementCheck::query()->where('attendee_id', $attendee->id)->count())->toBe(1);
});

it('applies event-level requirement to ticket types of that event', function () {
    $user = StubUser::create(['email' => 'engine-event-level@example.com']);
    $event = Event::factory()->create();
    $type = TicketType::factory()->create(['event_id' => $event->id]);
    Requirement::factory()->create([
        'event_id' => $event->id,
        'ticket_type_id' => null,
        'type' => RequirementType::AgeMin,
        'payload' => ['min' => 18],
        'strict' => true,
    ]);

    $attendee = Attendee::factory()->create([
        'event_id' => $event->id,
        'user_id' => $user->id,
        'profile' => ['date_of_birth' => now()->subYears(25)->toDateString()],
    ]);

    $outcome = app(RequirementEngine::class)->evaluateFor($attendee, $type);

    expect($outcome->checks)->toHaveCount(1);
    expect($outcome->allPassed)->toBeTrue();
});

it('ticket-type requirement does not apply to a different ticket type', function () {
    $user = StubUser::create(['email' => 'engine-typescope@example.com']);
    $event = Event::factory()->create();
    $typeA = TicketType::factory()->create(['event_id' => $event->id]);
    $typeB = TicketType::factory()->create(['event_id' => $event->id]);

    // Requirement attached only to typeA.
    Requirement::factory()->create([
        'event_id' => null,
        'ticket_type_id' => $typeA->id,
        'type' => RequirementType::AgeMin,
        'payload' => ['min' => 18],
        'strict' => true,
    ]);

    $attendee = Attendee::factory()->create([
        'event_id' => $event->id,
        'user_id' => $user->id,
        'profile' => ['date_of_birth' => now()->subYears(25)->toDateString()],
    ]);

    $outcomeForB = app(RequirementEngine::class)->evaluateFor($attendee, $typeB);

    // Evaluating against typeB should run only requirements whose event_id matches event
    // or ticket_type_id matches typeB. The typeA-scoped requirement should be excluded.
    // But our event_id filter would match it via event_id=null check. Actually the
    // requirement has event_id=null and ticket_type_id=typeA; for typeB the filter
    // (event_id=event OR ticket_type_id=typeB) yields no match.
    expect($outcomeForB->checks)->toHaveCount(0);
    expect($outcomeForB->allPassed)->toBeTrue();
});

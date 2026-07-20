<?php

declare(strict_types=1);

use Kurt\Modules\Events\Flow\Models\CheckInAttempt;
use Kurt\Modules\Events\Support\Events as EventsService;
use Kurt\Modules\Events\Tests\Stubs\StubUser;
use Kurt\Modules\Events\Ticketing\Enums\TicketStatus;
use Kurt\Modules\Events\Ticketing\Exceptions\TicketNotCheckInable;
use Kurt\Modules\Events\Ticketing\Models\Ticket;
use Kurt\Modules\Events\Ticketing\Support\QrTokenSigner;

beforeEach(function () {
    config()->set('kurtmodules.user_model', StubUser::class);
});

it('rejects a replayed check-in and records both a success and a failed attempt', function () {
    $scanner = StubUser::create(['email' => 's@x.com']);
    $ticket = Ticket::factory()->create(['status' => TicketStatus::Issued]);
    $events = app(EventsService::class);

    $events->checkIn($ticket, $scanner);
    expect($ticket->fresh()->status)->toBe(TicketStatus::CheckedIn);

    expect(fn () => $events->checkIn($ticket->fresh(), $scanner))
        ->toThrow(TicketNotCheckInable::class);

    expect(CheckInAttempt::query()->where('ticket_id', $ticket->id)->where('succeeded', true)->count())->toBe(1);
    expect(CheckInAttempt::query()->where('ticket_id', $ticket->id)->where('succeeded', false)->count())->toBe(1);

    // The replay must not re-stamp the check-in metadata.
    expect($ticket->fresh()->checked_in_by)->toBe($scanner->id);
});

it('rejects check-in of a refunded ticket', function () {
    $scanner = StubUser::create(['email' => 's@x.com']);
    $ticket = Ticket::factory()->create(['status' => TicketStatus::Refunded]);

    expect(fn () => app(EventsService::class)->checkIn($ticket, $scanner))
        ->toThrow(TicketNotCheckInable::class);

    expect(CheckInAttempt::query()->where('ticket_id', $ticket->id)->where('succeeded', false)->count())->toBe(1);
});

it('rejects check-in via a replayed QR token once the ticket is checked in', function () {
    $scanner = StubUser::create(['email' => 's@x.com']);
    $ticket = Ticket::factory()->create(['status' => TicketStatus::Issued]);
    $events = app(EventsService::class);

    $token = app(QrTokenSigner::class)->sign($ticket->id, $ticket->event_id);
    $events->checkInByToken($token, $scanner);

    expect(fn () => $events->checkInByToken($token, $scanner))
        ->toThrow(TicketNotCheckInable::class);
});

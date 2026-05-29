<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Kurt\Modules\Events\Flow\Enums\WaitlistStatus;
use Kurt\Modules\Events\Flow\Events\WaitlistPromoted;
use Kurt\Modules\Events\Flow\Models\WaitlistEntry;
use Kurt\Modules\Events\Flow\Support\WaitlistPromoter;
use Kurt\Modules\Events\Ticketing\Models\TicketType;

function waitlistPromoter(): WaitlistPromoter
{
    return new WaitlistPromoter(app('config'));
}

it('promotes the oldest waiting entry first (FIFO)', function () {
    Event::fake([WaitlistPromoted::class]);

    $type = TicketType::factory()->create();

    $older = WaitlistEntry::factory()->create([
        'ticket_type_id' => $type->id, 'user_id' => 1, 'status' => WaitlistStatus::Waiting,
        'created_at' => now()->subMinutes(10),
    ]);
    $newer = WaitlistEntry::factory()->create([
        'ticket_type_id' => $type->id, 'user_id' => 2, 'status' => WaitlistStatus::Waiting,
        'created_at' => now()->subMinutes(5),
    ]);

    $promoted = waitlistPromoter()->promoteNextFor($type);

    expect($promoted)->not->toBeNull();
    expect($promoted->id)->toBe($older->id);
    expect($promoted->status)->toBe(WaitlistStatus::Offered);
    expect($newer->fresh()->status)->toBe(WaitlistStatus::Waiting);

    Event::assertDispatched(
        WaitlistPromoted::class,
        fn (WaitlistPromoted $e) => $e->entry->id === $older->id,
    );
});

it('returns null when the waitlist is empty', function () {
    $type = TicketType::factory()->create();

    $promoted = waitlistPromoter()->promoteNextFor($type);

    expect($promoted)->toBeNull();
});

it('sets offered_at and claim_expires_at correctly', function () {
    config()->set('events.waitlist.claim_window_seconds', 300);

    $type = TicketType::factory()->create();
    $entry = WaitlistEntry::factory()->create([
        'ticket_type_id' => $type->id, 'user_id' => 1, 'status' => WaitlistStatus::Waiting,
    ]);

    $promoted = waitlistPromoter()->promoteNextFor($type);
    expect($promoted)->not->toBeNull();
    expect($promoted->offered_at)->not->toBeNull();
    expect($promoted->claim_expires_at)->not->toBeNull();
    expect((int) $promoted->offered_at->diffInSeconds($promoted->claim_expires_at, true))->toBe(300);
});

it('ignores already offered entries when promoting', function () {
    $type = TicketType::factory()->create();

    WaitlistEntry::factory()->create([
        'ticket_type_id' => $type->id, 'user_id' => 1, 'status' => WaitlistStatus::Offered,
    ]);
    $waiting = WaitlistEntry::factory()->create([
        'ticket_type_id' => $type->id, 'user_id' => 2, 'status' => WaitlistStatus::Waiting,
    ]);

    $promoted = waitlistPromoter()->promoteNextFor($type);

    expect($promoted->id)->toBe($waiting->id);
});

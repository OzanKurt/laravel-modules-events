<?php

declare(strict_types=1);

use Kurt\Modules\Events\Flow\Enums\WaitlistStatus;
use Kurt\Modules\Events\Flow\Exceptions\WaitlistClaimNotAllowed;
use Kurt\Modules\Events\Flow\Models\WaitlistEntry;
use Kurt\Modules\Events\Support\Events as EventsService;
use Kurt\Modules\Events\Tests\Stubs\StubUser;
use Kurt\Modules\Events\Ticketing\Enums\OrderStatus;
use Kurt\Modules\Events\Ticketing\Models\Order;
use Kurt\Modules\Events\Ticketing\Models\TicketType;

beforeEach(function () {
    config()->set('kurtmodules.user_model', StubUser::class);
});

it('rejects claiming a waitlist entry that was never offered', function () {
    $type = TicketType::factory()->create(['capacity' => 10, 'sold_count' => 0]);
    $user = StubUser::create(['email' => 'w@x.com']);
    $entry = WaitlistEntry::factory()->create([
        'ticket_type_id' => $type->id,
        'user_id' => $user->id,
        'status' => WaitlistStatus::Waiting,
    ]);

    expect(fn () => app(EventsService::class)->claimWaitlist($entry))
        ->toThrow(WaitlistClaimNotAllowed::class);

    expect(Order::query()->where('user_id', $user->id)->count())->toBe(0);
});

it('rejects claiming after the offer window has expired', function () {
    $type = TicketType::factory()->create(['capacity' => 10, 'sold_count' => 0]);
    $user = StubUser::create(['email' => 'w@x.com']);
    $entry = WaitlistEntry::factory()->create([
        'ticket_type_id' => $type->id,
        'user_id' => $user->id,
        'status' => WaitlistStatus::Offered,
        'offered_at' => now()->subMinutes(20),
        'claim_expires_at' => now()->subMinute(),
    ]);

    expect(fn () => app(EventsService::class)->claimWaitlist($entry))
        ->toThrow(WaitlistClaimNotAllowed::class);
});

it('claims a live offer once and rejects a second claim (idempotent)', function () {
    $type = TicketType::factory()->create(['price_minor' => 1000, 'currency' => 'USD', 'capacity' => 10, 'sold_count' => 0]);
    $user = StubUser::create(['name' => 'W', 'email' => 'w@x.com']);
    $entry = WaitlistEntry::factory()->create([
        'ticket_type_id' => $type->id,
        'user_id' => $user->id,
        'quantity' => 1,
        'status' => WaitlistStatus::Offered,
        'offered_at' => now(),
        'claim_expires_at' => now()->addMinutes(10),
    ]);

    $order = app(EventsService::class)->claimWaitlist($entry);
    expect($order->status)->toBe(OrderStatus::Pending);
    expect($entry->fresh()->status)->toBe(WaitlistStatus::Claimed);

    expect(fn () => app(EventsService::class)->claimWaitlist($entry->fresh()))
        ->toThrow(WaitlistClaimNotAllowed::class);

    // Only the first claim produced an order.
    expect(Order::query()->where('user_id', $user->id)->count())->toBe(1);
});

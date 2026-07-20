<?php

declare(strict_types=1);

use Kurt\Modules\Events\Catalog\Models\Event as CatalogEvent;
use Kurt\Modules\Events\Flow\Enums\QueueStatus;
use Kurt\Modules\Events\Flow\Exceptions\QueueChallengeFailed;
use Kurt\Modules\Events\Flow\Models\SaleQueueEntry;
use Kurt\Modules\Events\Flow\Support\QueuePruner;
use Kurt\Modules\Events\Support\Events as EventsService;
use Kurt\Modules\Events\Tests\Stubs\StubUser;
use Kurt\Modules\Events\Ticketing\Enums\OrderStatus;
use Kurt\Modules\Events\Ticketing\Models\TicketType;

beforeEach(function () {
    config()->set('kurtmodules.user_model', StubUser::class);
});

it('reserving is unaffected when the event has no sale queue', function () {
    $event = CatalogEvent::factory()->create();
    $type = TicketType::factory()->create(['event_id' => $event->id, 'capacity' => 100]);
    $buyer = StubUser::create(['email' => 'b@x.com']);

    $order = app(EventsService::class)->reserve($type, $buyer, 1, [['name' => 'A', 'email' => 'a@x.com']]);
    expect($order->status)->toBe(OrderStatus::Pending);
});

it('blocks reserving when the buyer holds no active queue admission', function () {
    $event = CatalogEvent::factory()->create();
    $type = TicketType::factory()->create(['event_id' => $event->id, 'capacity' => 100]);
    $buyer = StubUser::create(['email' => 'b@x.com']);

    // A queue is in use for this event, but the buyer is only Waiting.
    SaleQueueEntry::factory()->create(['event_id' => $event->id, 'user_id' => $buyer->id, 'status' => QueueStatus::Waiting]);

    expect(fn () => app(EventsService::class)->reserve($type, $buyer, 1, [['name' => 'A', 'email' => 'a@x.com']]))
        ->toThrow(QueueChallengeFailed::class);
});

it('permits reserving for an actively-admitted buyer', function () {
    $event = CatalogEvent::factory()->create();
    $type = TicketType::factory()->create(['event_id' => $event->id, 'capacity' => 100]);
    $buyer = StubUser::create(['email' => 'b@x.com']);

    SaleQueueEntry::factory()->create([
        'event_id' => $event->id,
        'user_id' => $buyer->id,
        'status' => QueueStatus::Active,
        'released_at' => now(),
        'expires_at' => now()->addMinutes(5),
    ]);

    $order = app(EventsService::class)->reserve($type, $buyer, 1, [['name' => 'A', 'email' => 'a@x.com']]);
    expect($order->status)->toBe(OrderStatus::Pending);
});

it('blocks an admitted buyer whose window has already expired', function () {
    $event = CatalogEvent::factory()->create();
    $type = TicketType::factory()->create(['event_id' => $event->id, 'capacity' => 100]);
    $buyer = StubUser::create(['email' => 'b@x.com']);

    SaleQueueEntry::factory()->create([
        'event_id' => $event->id,
        'user_id' => $buyer->id,
        'status' => QueueStatus::Active,
        'expires_at' => now()->subMinute(),
    ]);

    expect(fn () => app(EventsService::class)->reserve($type, $buyer, 1, [['name' => 'A', 'email' => 'a@x.com']]))
        ->toThrow(QueueChallengeFailed::class);
});

it('expireStaleActiveFor moves elapsed active admissions to Expired only', function () {
    $event = CatalogEvent::factory()->create();
    $fresh = SaleQueueEntry::factory()->create([
        'event_id' => $event->id, 'user_id' => 1,
        'status' => QueueStatus::Active, 'expires_at' => now()->addMinutes(5),
    ]);
    $stale = SaleQueueEntry::factory()->create([
        'event_id' => $event->id, 'user_id' => 2,
        'status' => QueueStatus::Active, 'expires_at' => now()->subMinute(),
    ]);

    $count = app(QueuePruner::class)->expireStaleActiveFor($event);

    expect($count)->toBe(1);
    expect($stale->fresh()->status)->toBe(QueueStatus::Expired);
    expect($fresh->fresh()->status)->toBe(QueueStatus::Active);
});

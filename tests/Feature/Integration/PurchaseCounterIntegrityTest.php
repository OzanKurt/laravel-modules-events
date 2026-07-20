<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Kurt\Modules\Events\Catalog\Models\Event as CatalogEvent;
use Kurt\Modules\Events\Support\Events as EventsService;
use Kurt\Modules\Events\Tests\Stubs\StubUser;
use Kurt\Modules\Events\Ticketing\Enums\OrderStatus;
use Kurt\Modules\Events\Ticketing\Events\OrderPaid;
use Kurt\Modules\Events\Ticketing\Models\Order;
use Kurt\Modules\Events\Ticketing\Models\TicketType;

beforeEach(function () {
    config()->set('kurtmodules.user_model', StubUser::class);
});

function reserveThree(CatalogEvent $event, StubUser $buyer): Order
{
    $type = TicketType::factory()->for($event)->create([
        'price_minor' => 1000,
        'currency' => 'USD',
        'capacity' => 10,
        'sold_count' => 0,
    ]);

    return app(EventsService::class)->reserve($type, $buyer, 3, [
        ['name' => 'A', 'email' => 'a@x.com', 'user_id' => null],
        ['name' => 'B', 'email' => 'b@x.com', 'user_id' => null],
        ['name' => 'C', 'email' => 'c@x.com', 'user_id' => null],
    ]);
}

it('dispatches OrderPaid exactly once when an order is paid', function () {
    Event::fake([OrderPaid::class]);

    $buyer = StubUser::create(['email' => 'buyer@x.com']);
    $event = CatalogEvent::factory()->create(['tickets_sold_count' => 0]);
    $order = reserveThree($event, $buyer);

    app(EventsService::class)->pay($order, 'stripe', 'ch_once');

    Event::assertDispatchedTimes(OrderPaid::class, 1);
});

it('does not double-count tickets_sold_count across reserve then pay', function () {
    $buyer = StubUser::create(['email' => 'buyer@x.com']);
    $event = CatalogEvent::factory()->create(['tickets_sold_count' => 0]);

    $order = reserveThree($event, $buyer);

    // Reservation must not touch the event-level counter (issued tickets only).
    expect($event->fresh()->tickets_sold_count)->toBe(0);

    app(EventsService::class)->pay($order, 'stripe', 'ch_pay');

    // After issuing 3 tickets the counter is exactly N, not 2N.
    expect($event->fresh()->tickets_sold_count)->toBe(3);
});

it('expiring a pending order leaves tickets_sold_count at zero and releases type capacity', function () {
    $buyer = StubUser::create(['email' => 'buyer@x.com']);
    $event = CatalogEvent::factory()->create(['tickets_sold_count' => 0]);

    $order = reserveThree($event, $buyer);
    expect($event->fresh()->tickets_sold_count)->toBe(0);

    $type = $order->items()->first()->ticketType()->first();
    expect($type->sold_count)->toBe(3);

    // Age the order past the pending timeout so the command picks it up.
    Order::query()->whereKey($order->id)->update(['created_at' => now()->subHour()]);

    $this->artisan('events:expire-pending-orders')->assertSuccessful();

    expect($order->fresh()->status)->toBe(OrderStatus::Cancelled);
    expect($event->fresh()->tickets_sold_count)->toBe(0);
    expect($type->fresh()->sold_count)->toBe(0);
});

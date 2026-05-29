<?php

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Event;
use Kurt\Modules\Events\Catalog\Models\Event as CatalogEvent;
use Kurt\Modules\Events\Console\Commands\ExpirePendingOrdersCommand;
use Kurt\Modules\Events\Ticketing\Enums\OrderStatus;
use Kurt\Modules\Events\Ticketing\Events\OrderCancelled;
use Kurt\Modules\Events\Ticketing\Models\Order;
use Kurt\Modules\Events\Ticketing\Models\OrderItem;
use Kurt\Modules\Events\Ticketing\Models\OrderItemAssignment;
use Kurt\Modules\Events\Ticketing\Models\TicketType;

beforeEach(function () {
    app(Kernel::class)->registerCommand(new ExpirePendingOrdersCommand);
});

it('cancels pending orders older than timeout and releases sold_count', function () {
    Event::fake([OrderCancelled::class]);
    config()->set('events.orders.pending_timeout_minutes', 15);

    $catalogEvent = CatalogEvent::factory()->create();
    $type = TicketType::factory()->create([
        'event_id' => $catalogEvent->id,
        'sold_count' => 5,
    ]);

    $stale = Order::factory()->create([
        'event_id' => $catalogEvent->id,
        'status' => OrderStatus::Pending,
        'created_at' => now()->subMinutes(30),
        'updated_at' => now()->subMinutes(30),
    ]);
    $staleItem = OrderItem::factory()->create([
        'order_id' => $stale->id,
        'ticket_type_id' => $type->id,
        'quantity' => 2,
    ]);
    OrderItemAssignment::factory()->create([
        'order_item_id' => $staleItem->id,
        'seat_index' => 0,
    ]);
    OrderItemAssignment::factory()->create([
        'order_item_id' => $staleItem->id,
        'seat_index' => 1,
    ]);

    Order::factory()->create([
        'event_id' => $catalogEvent->id,
        'status' => OrderStatus::Pending,
        'created_at' => now()->subMinutes(2),
        'updated_at' => now()->subMinutes(2),
    ]);

    $exit = Artisan::call('events:expire-pending-orders');

    expect($exit)->toBe(0);

    $stale->refresh();
    expect($stale->status)->toBe(OrderStatus::Cancelled);

    $type->refresh();
    expect($type->sold_count)->toBe(3);

    expect(OrderItemAssignment::query()->where('order_item_id', $staleItem->id)->count())->toBe(0);

    Event::assertDispatched(
        OrderCancelled::class,
        fn (OrderCancelled $e) => $e->order->id === $stale->id && $e->reason === 'cart_timeout',
    );
});

it('clamps sold_count to 0 instead of going negative', function () {
    config()->set('events.orders.pending_timeout_minutes', 15);

    $catalogEvent = CatalogEvent::factory()->create();
    $type = TicketType::factory()->create([
        'event_id' => $catalogEvent->id,
        'sold_count' => 1,
    ]);

    $stale = Order::factory()->create([
        'event_id' => $catalogEvent->id,
        'status' => OrderStatus::Pending,
        'created_at' => now()->subMinutes(30),
        'updated_at' => now()->subMinutes(30),
    ]);
    OrderItem::factory()->create([
        'order_id' => $stale->id,
        'ticket_type_id' => $type->id,
        'quantity' => 5,
    ]);

    Artisan::call('events:expire-pending-orders');

    $type->refresh();
    expect($type->sold_count)->toBe(0);
});

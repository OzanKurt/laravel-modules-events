<?php

declare(strict_types=1);

use Kurt\Modules\Events\Ticketing\Models\Order;
use Kurt\Modules\Events\Ticketing\Models\OrderItem;
use Kurt\Modules\Events\Ticketing\Models\OrderItemAssignment;
use Kurt\Modules\Events\Ticketing\Models\TicketType;

it('belongs to order and ticket type', function () {
    $order = Order::factory()->create();
    $type = TicketType::factory()->create();
    $item = OrderItem::factory()->create([
        'order_id' => $order->id,
        'ticket_type_id' => $type->id,
    ]);

    expect($item->order->id)->toBe($order->id);
    expect($item->ticketType->id)->toBe($type->id);
});

it('hasMany assignments', function () {
    $item = OrderItem::factory()->create(['quantity' => 2]);
    OrderItemAssignment::factory()->create(['order_item_id' => $item->id, 'seat_index' => 0]);
    OrderItemAssignment::factory()->create(['order_item_id' => $item->id, 'seat_index' => 1]);

    expect($item->assignments()->count())->toBe(2);
});

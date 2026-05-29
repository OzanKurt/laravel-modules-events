<?php

declare(strict_types=1);

use Kurt\Modules\Events\Flow\Enums\RefundStatus;
use Kurt\Modules\Events\Flow\Models\Refund;
use Kurt\Modules\Events\Ticketing\Enums\OrderStatus;
use Kurt\Modules\Events\Ticketing\Models\Order;

it('creates an order with totals', function () {
    $order = Order::factory()->create([
        'subtotal_minor' => 1000,
        'total_minor' => 1000,
    ]);

    expect($order->status)->toBe(OrderStatus::Pending);
    expect($order->total_minor)->toBe(1000);
});

it('recomputeTotalsAfterRefund flips to PartiallyRefunded when refund < total', function () {
    $order = Order::factory()->create(['total_minor' => 1000, 'status' => OrderStatus::Paid]);
    Refund::factory()->create([
        'order_id' => $order->id,
        'amount_minor' => 500,
        'status' => RefundStatus::Processed,
    ]);

    $order->recomputeTotalsAfterRefund();

    expect($order->fresh()?->status)->toBe(OrderStatus::PartiallyRefunded);
});

it('recomputeTotalsAfterRefund flips to Refunded when refund >= total', function () {
    $order = Order::factory()->create(['total_minor' => 1000, 'status' => OrderStatus::Paid]);
    Refund::factory()->create([
        'order_id' => $order->id,
        'amount_minor' => 1000,
        'status' => RefundStatus::Processed,
    ]);

    $order->recomputeTotalsAfterRefund();

    expect($order->fresh()?->status)->toBe(OrderStatus::Refunded);
});

it('only counts processed refunds', function () {
    $order = Order::factory()->create(['total_minor' => 1000, 'status' => OrderStatus::Paid]);
    Refund::factory()->create([
        'order_id' => $order->id,
        'amount_minor' => 500,
        'status' => RefundStatus::Pending,
    ]);

    $order->recomputeTotalsAfterRefund();

    expect($order->fresh()?->status)->toBe(OrderStatus::Paid);
});

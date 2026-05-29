<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Kurt\Modules\Events\Catalog\Models\Event as CatalogEvent;
use Kurt\Modules\Events\Flow\Enums\RefundReason;
use Kurt\Modules\Events\Flow\Enums\RefundStatus;
use Kurt\Modules\Events\Flow\Events\RefundProcessed;
use Kurt\Modules\Events\Flow\Events\RefundRequested;
use Kurt\Modules\Events\Flow\Exceptions\SelfCancellationNotPermitted;
use Kurt\Modules\Events\Flow\Support\RefundCoordinator;
use Kurt\Modules\Events\Tests\Stubs\StubUser;
use Kurt\Modules\Events\Ticketing\Enums\OrderStatus;
use Kurt\Modules\Events\Ticketing\Enums\TicketStatus;
use Kurt\Modules\Events\Ticketing\Models\Order;
use Kurt\Modules\Events\Ticketing\Models\OrderItem;
use Kurt\Modules\Events\Ticketing\Models\Ticket;
use Kurt\Modules\Events\Ticketing\Models\TicketType;

function refundCoordinator(): RefundCoordinator
{
    return new RefundCoordinator(app('config'));
}

it('request creates a Pending refund and dispatches RefundRequested', function () {
    Event::fake([RefundRequested::class]);

    $event = CatalogEvent::factory()->create();
    $order = Order::factory()->create(['event_id' => $event->id, 'total_minor' => 2_500, 'currency' => 'USD']);
    $user = StubUser::create(['email' => 'u@x.com']);

    $refund = refundCoordinator()->request($order, $user, RefundReason::AttendeeRequest, 'because');

    expect($refund->status)->toBe(RefundStatus::Pending);
    expect($refund->amount_minor)->toBe(2_500);
    expect($refund->currency)->toBe('USD');
    expect($refund->reason)->toBe(RefundReason::AttendeeRequest);
    expect($refund->reason_note)->toBe('because');
    expect($refund->requested_by)->toBe($user->id);

    Event::assertDispatched(RefundRequested::class);
});

it('markProcessed flips status, sets processor_reference, and updates order status', function () {
    Event::fake([RefundProcessed::class]);

    $event = CatalogEvent::factory()->create();
    $order = Order::factory()->paid()->create(['event_id' => $event->id, 'total_minor' => 1_000]);
    $user = StubUser::create(['email' => 'u@x.com']);

    $refund = refundCoordinator()->request($order, $user, RefundReason::OrganizerInitiated);

    refundCoordinator()->markProcessed($refund, 'pi_test_1');

    $refund->refresh();
    expect($refund->status)->toBe(RefundStatus::Processed);
    expect($refund->processor_reference)->toBe('pi_test_1');
    expect($refund->processed_at)->not->toBeNull();
    expect($order->fresh()->status)->toBe(OrderStatus::Refunded);

    Event::assertDispatched(RefundProcessed::class);
});

it('partial refund leaves the order in PartiallyRefunded', function () {
    $event = CatalogEvent::factory()->create();
    $order = Order::factory()->paid()->create(['event_id' => $event->id, 'total_minor' => 1_000]);
    $user = StubUser::create(['email' => 'u@x.com']);

    $refund = refundCoordinator()->request($order, $user, RefundReason::AttendeeRequest, null, 400);

    refundCoordinator()->markProcessed($refund, 'pi_test_2');

    expect($order->fresh()->status)->toBe(OrderStatus::PartiallyRefunded);
});

it('markFailed appends note text', function () {
    $event = CatalogEvent::factory()->create();
    $order = Order::factory()->create(['event_id' => $event->id]);
    $user = StubUser::create(['email' => 'u@x.com']);

    $refund = refundCoordinator()->request($order, $user, RefundReason::AttendeeRequest, 'first');
    refundCoordinator()->markFailed($refund, 'processor_failure');

    $refund->refresh();
    expect($refund->status)->toBe(RefundStatus::Failed);
    expect($refund->reason_note)->toContain('first');
    expect($refund->reason_note)->toContain('processor_failure');
});

it('allows EU self-cancel when paid recently and ticket type is not exempt', function () {
    config()->set('events.refunds.consumer_protection_window_days', 14);

    $event = CatalogEvent::factory()->create(['starts_at' => now()->addDays(30), 'ends_at' => now()->addDays(30)->addHour()]);
    $buyer = StubUser::create(['email' => 'buyer@x.com']);
    $order = Order::factory()->paid()->create([
        'event_id' => $event->id, 'user_id' => $buyer->id,
        'paid_at' => now()->subDays(5),
    ]);
    $type = TicketType::factory()->create([
        'event_id' => $event->id,
        'consumer_protection_exempt' => false,
        'self_cancel_deadline_hours_before_event' => null,
    ]);
    $item = OrderItem::factory()->create(['order_id' => $order->id, 'ticket_type_id' => $type->id]);
    Ticket::factory()->create([
        'order_item_id' => $item->id, 'ticket_type_id' => $type->id, 'event_id' => $event->id,
        'status' => TicketStatus::Issued,
    ]);

    $refund = refundCoordinator()->cancelOrderByBuyer($order, $buyer);

    expect($refund->status)->toBe(RefundStatus::Pending);
    expect($refund->metadata['consumer_protection_eligible'])->toBeTrue();
});

it('throws SelfCancellationNotPermitted when EU window expired and no per-type deadline', function () {
    config()->set('events.refunds.consumer_protection_window_days', 14);

    $event = CatalogEvent::factory()->create(['starts_at' => now()->addDays(30), 'ends_at' => now()->addDays(30)->addHour()]);
    $buyer = StubUser::create(['email' => 'buyer@x.com']);
    $order = Order::factory()->paid()->create([
        'event_id' => $event->id, 'user_id' => $buyer->id,
        'paid_at' => now()->subDays(20),
    ]);
    $type = TicketType::factory()->create([
        'event_id' => $event->id,
        'consumer_protection_exempt' => false,
        'self_cancel_deadline_hours_before_event' => null,
    ]);
    $item = OrderItem::factory()->create(['order_id' => $order->id, 'ticket_type_id' => $type->id]);
    Ticket::factory()->create([
        'order_item_id' => $item->id, 'ticket_type_id' => $type->id, 'event_id' => $event->id,
        'status' => TicketStatus::Issued,
    ]);

    expect(fn () => refundCoordinator()->cancelOrderByBuyer($order, $buyer))
        ->toThrow(SelfCancellationNotPermitted::class);
});

it('allows cancel via per-type deadline even when EU window expired', function () {
    config()->set('events.refunds.consumer_protection_window_days', 14);

    $event = CatalogEvent::factory()->create(['starts_at' => now()->addDays(30), 'ends_at' => now()->addDays(30)->addHour()]);
    $buyer = StubUser::create(['email' => 'buyer@x.com']);
    $order = Order::factory()->paid()->create([
        'event_id' => $event->id, 'user_id' => $buyer->id,
        'paid_at' => now()->subDays(20),
    ]);
    // 240 hours = 10 days before event → cutoff is 20 days from now → now is before cutoff
    $type = TicketType::factory()->create([
        'event_id' => $event->id,
        'consumer_protection_exempt' => false,
        'self_cancel_deadline_hours_before_event' => 240,
    ]);
    $item = OrderItem::factory()->create(['order_id' => $order->id, 'ticket_type_id' => $type->id]);
    Ticket::factory()->create([
        'order_item_id' => $item->id, 'ticket_type_id' => $type->id, 'event_id' => $event->id,
        'status' => TicketStatus::Issued,
    ]);

    $refund = refundCoordinator()->cancelOrderByBuyer($order, $buyer);

    expect($refund->status)->toBe(RefundStatus::Pending);
    expect($refund->metadata['consumer_protection_eligible'])->toBeFalse();
});

it('throws when ticket type is consumer_protection_exempt', function () {
    config()->set('events.refunds.consumer_protection_window_days', 14);

    $event = CatalogEvent::factory()->create(['starts_at' => now()->addDays(30), 'ends_at' => now()->addDays(30)->addHour()]);
    $buyer = StubUser::create(['email' => 'buyer@x.com']);
    $order = Order::factory()->paid()->create([
        'event_id' => $event->id, 'user_id' => $buyer->id,
        'paid_at' => now()->subDays(5),
    ]);
    $type = TicketType::factory()->create([
        'event_id' => $event->id,
        'consumer_protection_exempt' => true,
        'self_cancel_deadline_hours_before_event' => null,
    ]);
    $item = OrderItem::factory()->create(['order_id' => $order->id, 'ticket_type_id' => $type->id]);
    Ticket::factory()->create([
        'order_item_id' => $item->id, 'ticket_type_id' => $type->id, 'event_id' => $event->id,
        'status' => TicketStatus::Issued,
    ]);

    expect(fn () => refundCoordinator()->cancelOrderByBuyer($order, $buyer))
        ->toThrow(SelfCancellationNotPermitted::class);
});

it('throws when any ticket is already checked in', function () {
    $event = CatalogEvent::factory()->create();
    $buyer = StubUser::create(['email' => 'buyer@x.com']);
    $order = Order::factory()->paid()->create(['event_id' => $event->id, 'user_id' => $buyer->id]);
    $type = TicketType::factory()->create(['event_id' => $event->id]);
    $item = OrderItem::factory()->create(['order_id' => $order->id, 'ticket_type_id' => $type->id]);
    Ticket::factory()->checkedIn()->create([
        'order_item_id' => $item->id, 'ticket_type_id' => $type->id, 'event_id' => $event->id,
    ]);

    expect(fn () => refundCoordinator()->cancelOrderByBuyer($order, $buyer))
        ->toThrow(RuntimeException::class, 'check-in');
});

it('throws when caller is not the order buyer', function () {
    $event = CatalogEvent::factory()->create();
    $buyer = StubUser::create(['email' => 'buyer@x.com']);
    $other = StubUser::create(['email' => 'other@x.com']);
    $order = Order::factory()->paid()->create(['event_id' => $event->id, 'user_id' => $buyer->id]);

    expect(fn () => refundCoordinator()->cancelOrderByBuyer($order, $other))
        ->toThrow(RuntimeException::class, 'Not the order buyer');
});

it('throws when order is not Paid', function () {
    $event = CatalogEvent::factory()->create();
    $buyer = StubUser::create(['email' => 'buyer@x.com']);
    $order = Order::factory()->create(['event_id' => $event->id, 'user_id' => $buyer->id, 'status' => OrderStatus::Pending]);

    expect(fn () => refundCoordinator()->cancelOrderByBuyer($order, $buyer))
        ->toThrow(RuntimeException::class, 'paid orders');
});

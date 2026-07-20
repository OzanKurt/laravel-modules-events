<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Flow\Support;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\Eloquent\Model;
use Kurt\Modules\Events\Flow\Enums\RefundReason;
use Kurt\Modules\Events\Flow\Enums\RefundStatus;
use Kurt\Modules\Events\Flow\Events\RefundProcessed;
use Kurt\Modules\Events\Flow\Events\RefundRequested;
use Kurt\Modules\Events\Flow\Exceptions\SelfCancellationNotPermitted;
use Kurt\Modules\Events\Flow\Models\Refund;
use Kurt\Modules\Events\Ticketing\Enums\OrderStatus;
use Kurt\Modules\Events\Ticketing\Enums\TicketStatus;
use Kurt\Modules\Events\Ticketing\Models\Order;
use Kurt\Modules\Events\Ticketing\Models\OrderItem;
use Kurt\Modules\Events\Ticketing\Models\Ticket;
use Kurt\Modules\Events\Ticketing\Models\TicketType;

final class RefundCoordinator
{
    public function __construct(private readonly Repository $config) {}

    public function request(Order|Ticket $target, Model $requester, RefundReason $reason, ?string $note = null, ?int $amountMinor = null): Refund
    {
        if ($target instanceof Ticket) {
            $orderItem = $target->orderItem()->firstOrFail();
            $order = $orderItem->order()->firstOrFail();
            $amount = $amountMinor ?? (int) $orderItem->unit_price_minor;
        } else {
            $order = $target;
            $amount = $amountMinor ?? (int) $order->total_minor;
        }

        $refund = Refund::create([
            'order_id' => $order->id,
            'ticket_id' => $target instanceof Ticket ? $target->id : null,
            'amount_minor' => $amount,
            'currency' => (string) $order->currency,
            'reason' => $reason,
            'reason_note' => $note,
            'status' => RefundStatus::Pending,
            'requested_by' => $requester->getKey(),
        ]);

        RefundRequested::dispatch($refund);

        return $refund;
    }

    public function markProcessed(Refund $refund, string $processorReference): void
    {
        // Idempotency guard: only a Pending refund can be processed. Re-processing an
        // already-Processed (or Failed) refund would re-recompute order totals and
        // re-dispatch RefundProcessed, so short-circuit here.
        if ($refund->status !== RefundStatus::Pending) {
            return;
        }

        $refund->forceFill([
            'status' => RefundStatus::Processed,
            'processor_reference' => $processorReference,
            'processed_at' => now(),
        ])->save();

        $refund->order()->firstOrFail()->recomputeTotalsAfterRefund();
        RefundProcessed::dispatch($refund);
    }

    public function markFailed(Refund $refund, string $note): void
    {
        $refund->forceFill([
            'status' => RefundStatus::Failed,
            'reason_note' => trim(($refund->reason_note ?? '').PHP_EOL.$note),
        ])->save();
    }

    public function cancelOrderByBuyer(Order $order, Model $buyer): Refund
    {
        if ($order->user_id !== $buyer->getKey()) {
            throw new \RuntimeException('Not the order buyer');
        }
        if ($order->status !== OrderStatus::Paid) {
            throw new \RuntimeException('Only paid orders can be self-cancelled');
        }

        $tickets = $order->tickets()->get();
        foreach ($tickets as $ticket) {
            if ($ticket->status === TicketStatus::CheckedIn) {
                throw new \RuntimeException('Cannot self-cancel after check-in');
            }
        }

        $allowedByEu = $this->isInConsumerProtectionWindow($order);

        $event = $order->event()->firstOrFail();
        $items = $order->items()->with('ticketType')->get();
        $allowedBySelfCancelDeadline = $items->isNotEmpty() && $items->every(function (OrderItem $item) use ($event) {
            $type = $item->ticketType()->firstOrFail();
            $hours = $type->self_cancel_deadline_hours_before_event;
            if ($hours === null) {
                return false;
            }
            $cutoff = $event->starts_at->copy()->subHours((int) $hours);

            return now()->lt($cutoff);
        });

        if (! $allowedByEu && ! $allowedBySelfCancelDeadline) {
            throw new SelfCancellationNotPermitted;
        }

        $refund = $this->request($order, $buyer, RefundReason::AttendeeRequest, 'Buyer self-cancellation');
        $refund->forceFill([
            'metadata' => ['consumer_protection_eligible' => $allowedByEu],
        ])->save();

        return $refund;
    }

    private function isInConsumerProtectionWindow(Order $order): bool
    {
        $windowDays = (int) $this->config->get('events.refunds.consumer_protection_window_days', 14);
        if ($windowDays === 0) {
            return false;
        }
        if ($order->paid_at === null || $order->paid_at->lt(now()->subDays($windowDays))) {
            return false;
        }

        $items = $order->items()->with('ticketType')->get();

        return $items->every(function (OrderItem $i): bool {
            $type = $this->ticketTypeOf($i);

            return $type !== null && ! $type->consumer_protection_exempt;
        });
    }

    private function ticketTypeOf(OrderItem $item): ?TicketType
    {
        // Uses the eager-loaded relation (see the ->with('ticketType') above)
        // instead of issuing a fresh query per call.
        return $item->ticketType;
    }
}

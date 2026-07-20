<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Flow\Support;

use Illuminate\Support\Facades\DB;
use Kurt\Modules\Events\Catalog\Models\EventOrganizer;
use Kurt\Modules\Events\Flow\Enums\PayoutStatus;
use Kurt\Modules\Events\Flow\Enums\RefundStatus;
use Kurt\Modules\Events\Flow\Models\PayoutLedgerEntry;
use Kurt\Modules\Events\Ticketing\Models\Order;

final class PayoutAccruer
{
    public function accrueFor(Order $order): void
    {
        DB::transaction(function () use ($order): void {
            $organizers = EventOrganizer::query()
                ->where('event_id', $order->event_id)
                ->whereNotNull('commission_basis_points')
                ->get();

            // Accrue on net revenue (order total minus already-processed refunds), never
            // on gross. When no refunds exist this equals the full total.
            $net = $this->netRevenueMinor($order);

            foreach ($organizers as $organizer) {
                $bps = (int) $organizer->commission_basis_points;
                $amount = intdiv($net * $bps, 10_000);

                // Idempotent: at most one ledger entry per (order, organizer). Accruing the
                // same order twice (e.g. OrderObserver auto-accrual plus a manual call) must
                // not create duplicate entries. Backed by a unique index on the table.
                PayoutLedgerEntry::firstOrCreate(
                    [
                        'order_id' => $order->id,
                        'organizer_user_id' => $organizer->user_id,
                    ],
                    [
                        'share_basis_points' => $bps,
                        'amount_minor' => $amount,
                        'currency' => $order->currency,
                        'status' => PayoutStatus::Accrued,
                    ],
                );
            }
        });
    }

    /**
     * Re-cost still-accrued ledger entries after a refund is processed, so the payout
     * reflects net revenue. Settled entries (paid out or reversed) are left untouched.
     */
    public function reconcileForRefund(Order $order): void
    {
        DB::transaction(function () use ($order): void {
            $net = $this->netRevenueMinor($order);

            $entries = PayoutLedgerEntry::query()
                ->where('order_id', $order->id)
                ->where('status', PayoutStatus::Accrued->value)
                ->lockForUpdate()
                ->get();

            foreach ($entries as $entry) {
                $bps = (int) $entry->share_basis_points;
                $entry->forceFill(['amount_minor' => intdiv($net * $bps, 10_000)])->save();
            }
        });
    }

    private function netRevenueMinor(Order $order): int
    {
        $refunded = (int) $order->refunds()
            ->where('status', RefundStatus::Processed->value)
            ->sum('amount_minor');

        return max(0, (int) $order->total_minor - $refunded);
    }
}

<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Flow\Support;

use Kurt\Modules\Events\Catalog\Models\EventOrganizer;
use Kurt\Modules\Events\Flow\Enums\PayoutStatus;
use Kurt\Modules\Events\Flow\Models\PayoutLedgerEntry;
use Kurt\Modules\Events\Ticketing\Models\Order;

final class PayoutAccruer
{
    public function accrueFor(Order $order): void
    {
        $organizers = EventOrganizer::query()
            ->where('event_id', $order->event_id)
            ->whereNotNull('commission_basis_points')
            ->get();

        foreach ($organizers as $organizer) {
            $bps = (int) $organizer->commission_basis_points;
            $amount = intdiv($order->total_minor * $bps, 10_000);

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
    }
}

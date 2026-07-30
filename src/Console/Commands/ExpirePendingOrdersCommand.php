<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Kurt\Modules\Events\Ticketing\Enums\OrderStatus;
use Kurt\Modules\Events\Ticketing\Events\OrderCancelled;
use Kurt\Modules\Events\Ticketing\Models\Order;
use Kurt\Modules\Events\Ticketing\Models\PriceTier;
use Kurt\Modules\Events\Ticketing\Models\TicketType;

final class ExpirePendingOrdersCommand extends Command
{
    /** @var string */
    protected $signature = 'events:expire-pending-orders';

    /** @var string */
    protected $description = 'Cancel orders stuck in pending past the timeout and release capacity.';

    public function handle(): int
    {
        $cutoff = now()->subMinutes((int) config('events.orders.pending_timeout_minutes', 15));

        $orders = Order::query()
            ->where('status', OrderStatus::Pending->value)
            ->where('created_at', '<', $cutoff)
            ->with('items')
            ->get();

        foreach ($orders as $order) {
            DB::transaction(function () use ($order): void {
                foreach ($order->items as $item) {
                    $qty = (int) $item->quantity;

                    $this->releaseSoldCount(
                        TicketType::query()->where('id', $item->ticket_type_id),
                        $qty,
                    );

                    // Release the reserved price-tier capacity too, mirroring the
                    // per-tier increment done at reservation time.
                    if ($item->price_tier_id !== null) {
                        $this->releaseSoldCount(
                            PriceTier::query()->where('id', $item->price_tier_id),
                            $qty,
                        );
                    }

                    $item->assignments()->delete();
                }

                $order->forceFill(['status' => OrderStatus::Cancelled])->save();
                OrderCancelled::dispatch($order, 'cart_timeout');
            });
        }

        $count = $orders->count();
        $this->info("Cancelled {$count} pending order(s).");

        return self::SUCCESS;
    }

    /**
     * Give back $qty of reserved capacity without ever dropping below zero.
     *
     * Both statements are set-based, so the clamp stays atomic per row. Order
     * matters: rows that hold less than $qty are floored to zero first, which
     * leaves only rows that can absorb the full decrement for the second
     * statement. Doing it the other way round would re-clamp rows that were
     * just legitimately decremented below $qty.
     *
     * @param  Builder<covariant Model>  $query
     */
    private function releaseSoldCount(Builder $query, int $qty): void
    {
        (clone $query)->where('sold_count', '<', $qty)->update(['sold_count' => 0]);
        (clone $query)->where('sold_count', '>=', $qty)->decrement('sold_count', $qty);
    }
}

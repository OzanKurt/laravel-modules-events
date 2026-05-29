<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Kurt\Modules\Events\Ticketing\Enums\OrderStatus;
use Kurt\Modules\Events\Ticketing\Events\OrderCancelled;
use Kurt\Modules\Events\Ticketing\Models\Order;
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
                    TicketType::query()
                        ->where('id', $item->ticket_type_id)
                        ->lockForUpdate()
                        ->update([
                            'sold_count' => DB::raw('CASE WHEN sold_count >= '.$qty.' THEN sold_count - '.$qty.' ELSE 0 END'),
                        ]);

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
}

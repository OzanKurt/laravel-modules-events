<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Console\Commands;

use Illuminate\Console\Command;
use Kurt\Modules\Events\Catalog\Models\Event;
use Kurt\Modules\Events\Flow\Support\QueuePruner;

final class PruneQueueCommand extends Command
{
    /** @var string */
    protected $signature = 'events:prune-queue';

    /** @var string */
    protected $description = 'Mark waiting queue entries past the heartbeat timeout as abandoned.';

    public function handle(QueuePruner $pruner): int
    {
        $now = now();
        $events = Event::query()
            ->where('sale_starts_at', '<=', $now)
            ->where(function ($q) use ($now): void {
                $q->whereNull('sale_ends_at')->orWhere('sale_ends_at', '>', $now);
            })
            ->get();

        $pruned = 0;
        foreach ($events as $event) {
            $pruned += $pruner->pruneFor($event);
        }

        $this->info("Pruned {$pruned} stale queue entries across {$events->count()} event(s).");

        return self::SUCCESS;
    }
}

<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Console\Commands;

use Illuminate\Console\Command;
use Kurt\Modules\Events\Catalog\Models\Event;
use Kurt\Modules\Events\Flow\Support\QueueReleaser;

final class ReleaseQueueCommand extends Command
{
    /** @var string */
    protected $signature = 'events:release-queue';

    /** @var string */
    protected $description = 'Release queued users into active sale window for events with open sales.';

    public function handle(QueueReleaser $releaser): int
    {
        $now = now();
        $events = Event::query()
            ->where('sale_starts_at', '<=', $now)
            ->where(function ($q) use ($now): void {
                $q->whereNull('sale_ends_at')->orWhere('sale_ends_at', '>', $now);
            })
            ->get();

        $promoted = 0;
        foreach ($events as $event) {
            $promoted += $releaser->releaseFor($event);
        }

        $this->info("Promoted {$promoted} queue entries across {$events->count()} event(s).");

        return self::SUCCESS;
    }
}

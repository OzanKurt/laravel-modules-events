<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Console\Commands;

use Illuminate\Console\Command;
use Kurt\Modules\Events\Catalog\Enums\EventStatus;
use Kurt\Modules\Events\Catalog\Models\Event;
use Kurt\Modules\Events\Catalog\Support\RecurrenceExpander;

final class GenerateOccurrencesCommand extends Command
{
    /** @var string */
    protected $signature = 'events:generate-occurrences';

    /** @var string */
    protected $description = 'Materialise occurrences for published recurring events within the configured window.';

    public function handle(RecurrenceExpander $expander): int
    {
        $windowDays = (int) config('events.recurrence.window_days', 90);

        $parents = Event::query()
            ->whereNull('parent_event_id')
            ->whereNotNull('recurrence_rule')
            ->where('status', EventStatus::Published->value)
            ->get();

        $generated = 0;
        foreach ($parents as $parent) {
            $generated += $expander->expand($parent, $windowDays);
        }

        $this->info("Generated {$generated} occurrence(s) across {$parents->count()} parent event(s).");

        return self::SUCCESS;
    }
}

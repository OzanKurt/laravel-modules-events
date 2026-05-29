<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Console\Commands;

use Illuminate\Console\Command;
use Kurt\Modules\Events\Attendance\Models\Announcement;
use Kurt\Modules\Events\Attendance\Support\AnnouncementDispatcher;

final class DispatchAnnouncementsCommand extends Command
{
    /** @var string */
    protected $signature = 'events:dispatch-announcements';

    /** @var string */
    protected $description = 'Send announcements whose scheduled_for time has arrived.';

    public function handle(AnnouncementDispatcher $dispatcher): int
    {
        $announcements = Announcement::query()
            ->whereNotNull('scheduled_for')
            ->where('scheduled_for', '<=', now())
            ->whereNull('sent_at')
            ->get();

        $count = 0;
        foreach ($announcements as $announcement) {
            $dispatcher->dispatch($announcement);
            $count++;
        }

        $this->info("Dispatched {$count} scheduled announcement(s).");

        return self::SUCCESS;
    }
}

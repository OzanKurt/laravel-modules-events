<?php

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Kurt\Modules\Events\Catalog\Models\Event as CatalogEvent;
use Kurt\Modules\Events\Console\Commands\ReleaseQueueCommand;
use Kurt\Modules\Events\Flow\Enums\QueueStatus;
use Kurt\Modules\Events\Flow\Models\SaleQueueEntry;
use Kurt\Modules\Events\Flow\Support\QueueReleaser;

beforeEach(function () {
    app(Kernel::class)->registerCommand(new ReleaseQueueCommand);
});

it('releases waiting queue entries for events with open sales', function () {
    config()->set('events.queue.active_concurrency', 5);

    $event = CatalogEvent::factory()->create([
        'sale_starts_at' => now()->subMinute(),
        'sale_ends_at' => now()->addHour(),
    ]);
    foreach (range(1, 3) as $i) {
        SaleQueueEntry::factory()->create([
            'event_id' => $event->id,
            'user_id' => $i,
            'position' => $i,
            'status' => QueueStatus::Waiting,
        ]);
    }

    $this->app->instance(QueueReleaser::class, new QueueReleaser(app('config')));
    $exit = Artisan::call('events:release-queue');

    expect($exit)->toBe(0);
    expect(SaleQueueEntry::query()
        ->where('event_id', $event->id)
        ->where('status', QueueStatus::Active->value)
        ->count())->toBe(3);
});

it('ignores events whose sale window is closed', function () {
    config()->set('events.queue.active_concurrency', 5);
    $event = CatalogEvent::factory()->create([
        'sale_starts_at' => now()->subDay(),
        'sale_ends_at' => now()->subHour(),
    ]);
    SaleQueueEntry::factory()->create([
        'event_id' => $event->id,
        'user_id' => 1,
        'position' => 1,
        'status' => QueueStatus::Waiting,
    ]);

    $this->app->instance(QueueReleaser::class, new QueueReleaser(app('config')));
    Artisan::call('events:release-queue');

    expect(SaleQueueEntry::query()
        ->where('event_id', $event->id)
        ->where('status', QueueStatus::Waiting->value)
        ->count())->toBe(1);
});

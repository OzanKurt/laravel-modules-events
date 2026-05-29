<?php

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Kurt\Modules\Events\Catalog\Enums\EventStatus;
use Kurt\Modules\Events\Catalog\Models\Event as CatalogEvent;
use Kurt\Modules\Events\Catalog\Support\RecurrenceExpander;
use Kurt\Modules\Events\Console\Commands\GenerateOccurrencesCommand;

beforeEach(function () {
    app(Kernel::class)->registerCommand(new GenerateOccurrencesCommand);
});

it('expands recurring published events within the window', function () {
    config()->set('events.recurrence.window_days', 30);

    $start = now()->startOfDay()->addHour();
    $event = CatalogEvent::factory()->published()->create([
        'starts_at' => $start,
        'ends_at' => $start->copy()->addHour(),
        'recurrence_rule' => ['frequency' => 'weekly', 'interval' => 1],
    ]);

    $this->app->instance(RecurrenceExpander::class, new RecurrenceExpander);
    $exit = Artisan::call('events:generate-occurrences');

    expect($exit)->toBe(0);
    expect(CatalogEvent::query()->where('parent_event_id', $event->id)->count())->toBe(4);
});

it('skips non-published parent events', function () {
    config()->set('events.recurrence.window_days', 30);

    $start = now()->startOfDay()->addHour();
    $event = CatalogEvent::factory()->create([
        'status' => EventStatus::Draft,
        'starts_at' => $start,
        'ends_at' => $start->copy()->addHour(),
        'recurrence_rule' => ['frequency' => 'weekly', 'interval' => 1],
    ]);

    $this->app->instance(RecurrenceExpander::class, new RecurrenceExpander);
    Artisan::call('events:generate-occurrences');

    expect(CatalogEvent::query()->where('parent_event_id', $event->id)->count())->toBe(0);
});

it('skips events without a recurrence rule', function () {
    config()->set('events.recurrence.window_days', 30);
    CatalogEvent::factory()->published()->create(['recurrence_rule' => null]);

    $this->app->instance(RecurrenceExpander::class, new RecurrenceExpander);
    $exit = Artisan::call('events:generate-occurrences');

    expect($exit)->toBe(0);
});

<?php

declare(strict_types=1);

use Kurt\Modules\Events\Catalog\Models\Event;
use Kurt\Modules\Events\Catalog\Models\EventTag;

it('persists translatable name + slug', function () {
    $tag = EventTag::factory()->create(['name' => ['en' => 'Jazz']]);

    expect($tag->getTranslation('name', 'en'))->toBe('Jazz');
    expect($tag->slug)->not->toBeEmpty();
});

it('belongs to many events via pivot', function () {
    $tag = EventTag::factory()->create();
    $event = Event::factory()->create();
    $event->tags()->attach($tag);

    expect($tag->events()->pluck('events_events.id')->all())->toContain($event->id);
});

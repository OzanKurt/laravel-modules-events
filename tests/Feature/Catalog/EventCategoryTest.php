<?php

declare(strict_types=1);

use Kurt\Modules\Events\Catalog\Models\Event;
use Kurt\Modules\Events\Catalog\Models\EventCategory;

it('persists translatable name + slug', function () {
    $cat = EventCategory::factory()->create(['name' => ['en' => 'Music', 'tr' => 'Muzik']]);

    expect($cat->getTranslation('name', 'en'))->toBe('Music');
    expect($cat->getTranslation('name', 'tr'))->toBe('Muzik');
    expect($cat->slug)->not->toBeEmpty();
});

it('supports self-referential parent/children', function () {
    $parent = EventCategory::factory()->create();
    $child = EventCategory::factory()->create(['parent_id' => $parent->id]);

    expect($child->parent?->id)->toBe($parent->id);
    expect($parent->children()->pluck('id')->all())->toContain($child->id);
});

it('exposes events relation', function () {
    $cat = EventCategory::factory()->create();
    $event = Event::factory()->create(['category_id' => $cat->id]);

    expect($cat->events()->pluck('id')->all())->toContain($event->id);
});

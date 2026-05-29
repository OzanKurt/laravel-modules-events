<?php

declare(strict_types=1);

use Kurt\Modules\Events\Catalog\Models\Event;
use Kurt\Modules\Events\Catalog\Models\Session;

it('persists translatable title + slug', function () {
    $event = Event::factory()->create();
    $session = Session::factory()->create([
        'event_id' => $event->id,
        'title' => ['en' => 'Main Stage', 'tr' => 'Ana Sahne'],
    ]);

    expect($session->getTranslation('title', 'en'))->toBe('Main Stage');
    expect($session->getTranslation('title', 'tr'))->toBe('Ana Sahne');
    expect($session->slug)->not->toBeEmpty();
});

it('belongs to event', function () {
    $event = Event::factory()->create();
    $session = Session::factory()->create(['event_id' => $event->id]);

    expect($session->event->id)->toBe($event->id);
});

it('exposes ticketTypes and checkIns relations', function () {
    $session = Session::factory()->create();

    expect($session->ticketTypes())->not->toBeNull();
    expect($session->checkIns())->not->toBeNull();
});

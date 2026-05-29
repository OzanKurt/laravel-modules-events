<?php

declare(strict_types=1);

use Kurt\Modules\Events\Catalog\Models\Event;
use Kurt\Modules\Events\Ticketing\Enums\TicketTypeMode;
use Kurt\Modules\Events\Ticketing\Models\PriceTier;
use Kurt\Modules\Events\Ticketing\Models\TicketType;

it('persists translatable name', function () {
    $event = Event::factory()->create();
    $type = TicketType::factory()->create([
        'event_id' => $event->id,
        'name' => ['en' => 'General', 'tr' => 'Genel'],
    ]);

    expect($type->getTranslation('name', 'en'))->toBe('General');
    expect($type->getTranslation('name', 'tr'))->toBe('Genel');
});

it('casts mode, refundable, transferable', function () {
    $type = TicketType::factory()->rsvp()->create();

    expect($type->mode)->toBe(TicketTypeMode::Rsvp);
    expect($type->refundable)->toBeTrue();
    expect($type->transferable)->toBeTrue();
});

it('returns activePriceTier within active window', function () {
    $type = TicketType::factory()->create(['price_minor' => 1500]);

    PriceTier::factory()->create([
        'ticket_type_id' => $type->id,
        'name' => 'Early bird',
        'starts_at' => now()->subDays(2),
        'ends_at' => now()->subDay(),
        'price_minor' => 500,
        'position' => 0,
    ]);

    $regular = PriceTier::factory()->create([
        'ticket_type_id' => $type->id,
        'name' => 'Regular',
        'starts_at' => now()->subHour(),
        'ends_at' => now()->addDay(),
        'price_minor' => 1000,
        'position' => 1,
    ]);

    expect($type->activePriceTier()?->id)->toBe($regular->id);
    expect($type->currentUnitPriceMinor())->toBe(1000);
});

it('falls back to price_minor when no active tier', function () {
    $type = TicketType::factory()->create(['price_minor' => 2500]);

    expect($type->currentUnitPriceMinor())->toBe(2500);
});

<?php

declare(strict_types=1);

use Kurt\Modules\Events\Ticketing\Enums\AddOnPurchaseStatus;
use Kurt\Modules\Events\Ticketing\Models\TicketAddOn;
use Kurt\Modules\Events\Ticketing\Models\TicketAddOnPurchase;

it('persists translatable name', function () {
    $addOn = TicketAddOn::factory()->create(['name' => ['en' => 'Parking', 'tr' => 'Otopark']]);

    expect($addOn->getTranslation('name', 'en'))->toBe('Parking');
    expect($addOn->getTranslation('name', 'tr'))->toBe('Otopark');
});

it('casts scannable + active to boolean', function () {
    $addOn = TicketAddOn::factory()->scannable()->create();

    expect($addOn->scannable)->toBeTrue();
    expect($addOn->active)->toBeTrue();
});

it('purchase status casts to enum', function () {
    $purchase = TicketAddOnPurchase::factory()->create(['status' => AddOnPurchaseStatus::Paid]);

    expect($purchase->status)->toBe(AddOnPurchaseStatus::Paid);
});

<?php

declare(strict_types=1);

use Kurt\Modules\Events\Catalog\Models\Event as CatalogEvent;
use Kurt\Modules\Events\Catalog\Models\EventOrganizer;
use Kurt\Modules\Events\Flow\Enums\PayoutStatus;
use Kurt\Modules\Events\Flow\Models\PayoutLedgerEntry;
use Kurt\Modules\Events\Flow\Support\PayoutAccruer;
use Kurt\Modules\Events\Tests\Stubs\StubUser;
use Kurt\Modules\Events\Ticketing\Models\Order;

it('splits 60/40 across two organizers on a paid order', function () {
    $event = CatalogEvent::factory()->create();
    $orgA = StubUser::create(['email' => 'a@x.com']);
    $orgB = StubUser::create(['email' => 'b@x.com']);

    EventOrganizer::factory()->create([
        'event_id' => $event->id,
        'user_id' => $orgA->id,
        'commission_basis_points' => 6000,
    ]);
    EventOrganizer::factory()->create([
        'event_id' => $event->id,
        'user_id' => $orgB->id,
        'commission_basis_points' => 4000,
    ]);

    $order = Order::factory()->create([
        'event_id' => $event->id,
        'total_minor' => 100_000,
        'currency' => 'USD',
    ]);

    (new PayoutAccruer)->accrueFor($order);

    $entries = PayoutLedgerEntry::query()->where('order_id', $order->id)->orderBy('organizer_user_id')->get();
    expect($entries)->toHaveCount(2);

    $byUser = $entries->keyBy('organizer_user_id');
    expect($byUser[$orgA->id]->amount_minor)->toBe(60_000);
    expect($byUser[$orgA->id]->share_basis_points)->toBe(6000);
    expect($byUser[$orgA->id]->status)->toBe(PayoutStatus::Accrued);
    expect($byUser[$orgB->id]->amount_minor)->toBe(40_000);
    expect($byUser[$orgB->id]->share_basis_points)->toBe(4000);
});

it('is idempotent: accruing the same order twice yields one entry per organizer', function () {
    $event = CatalogEvent::factory()->create();
    $org = StubUser::create(['email' => 'once@x.com']);
    EventOrganizer::factory()->create([
        'event_id' => $event->id,
        'user_id' => $org->id,
        'commission_basis_points' => 5000,
    ]);

    $order = Order::factory()->create([
        'event_id' => $event->id,
        'total_minor' => 20_000,
        'currency' => 'USD',
    ]);

    (new PayoutAccruer)->accrueFor($order);
    (new PayoutAccruer)->accrueFor($order);

    $entries = PayoutLedgerEntry::query()->where('order_id', $order->id)->get();
    expect($entries)->toHaveCount(1);
    expect($entries->first()->amount_minor)->toBe(10_000);
});

it('does not create a row for organizers without commission_basis_points', function () {
    $event = CatalogEvent::factory()->create();
    $org = StubUser::create(['email' => 'no-commission@x.com']);
    EventOrganizer::factory()->create([
        'event_id' => $event->id,
        'user_id' => $org->id,
        'commission_basis_points' => null,
    ]);

    $order = Order::factory()->create(['event_id' => $event->id, 'total_minor' => 100_000]);

    (new PayoutAccruer)->accrueFor($order);

    expect(PayoutLedgerEntry::query()->where('order_id', $order->id)->count())->toBe(0);
});

it('carries the order currency through to ledger entries', function () {
    $event = CatalogEvent::factory()->create();
    $org = StubUser::create(['email' => 'org-eur@x.com']);
    EventOrganizer::factory()->create([
        'event_id' => $event->id,
        'user_id' => $org->id,
        'commission_basis_points' => 5000,
    ]);

    $order = Order::factory()->create([
        'event_id' => $event->id,
        'total_minor' => 50_000,
        'currency' => 'EUR',
    ]);

    (new PayoutAccruer)->accrueFor($order);

    $entry = PayoutLedgerEntry::query()->where('order_id', $order->id)->firstOrFail();
    expect($entry->currency)->toBe('EUR');
    expect($entry->amount_minor)->toBe(25_000);
});

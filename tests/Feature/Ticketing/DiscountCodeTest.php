<?php

declare(strict_types=1);

use Kurt\Modules\Events\Catalog\Models\Event;
use Kurt\Modules\Events\Tests\Stubs\StubUser;
use Kurt\Modules\Events\Ticketing\Enums\DiscountKind;
use Kurt\Modules\Events\Ticketing\Enums\DiscountScope;
use Kurt\Modules\Events\Ticketing\Models\DiscountCode;
use Kurt\Modules\Events\Ticketing\Models\DiscountCodeUsage;
use Kurt\Modules\Events\Ticketing\Models\Order;

it('isActive returns true for a fresh code', function () {
    $code = DiscountCode::factory()->create();

    expect($code->isActive())->toBeTrue();
});

it('isActive returns false when inactive flag is off', function () {
    $code = DiscountCode::factory()->create(['active' => false]);

    expect($code->isActive())->toBeFalse();
});

it('isActive false when expired', function () {
    $code = DiscountCode::factory()->create([
        'expires_at' => now()->subDay(),
    ]);

    expect($code->isActive())->toBeFalse();
});

it('isActive false when not started yet', function () {
    $code = DiscountCode::factory()->create([
        'starts_at' => now()->addDay(),
    ]);

    expect($code->isActive())->toBeFalse();
});

it('isActive false when max uses exhausted', function () {
    $code = DiscountCode::factory()->create([
        'max_uses_total' => 5,
        'uses_count' => 5,
    ]);

    expect($code->isActive())->toBeFalse();
});

it('appliesToEvent always true for global scope', function () {
    $code = DiscountCode::factory()->create();
    $event = Event::factory()->create();

    expect($code->appliesToEvent($event))->toBeTrue();
});

it('appliesToEvent false for subset scope when not attached', function () {
    $code = DiscountCode::factory()->scopedToEventsSubset()->create();
    $event = Event::factory()->create();

    expect($code->appliesToEvent($event))->toBeFalse();
});

it('appliesToEvent true for subset scope when attached', function () {
    $code = DiscountCode::factory()->scopedToEventsSubset()->create();
    $event = Event::factory()->create();
    $code->events()->attach($event);

    expect($code->appliesToEvent($event))->toBeTrue();
});

it('usedByUserCount returns count for user', function () {
    $code = DiscountCode::factory()->create();
    $user = StubUser::create(['email' => 'u@x.com']);
    $order = Order::factory()->create(['user_id' => $user->id]);
    DiscountCodeUsage::factory()->create([
        'discount_code_id' => $code->id,
        'order_id' => $order->id,
        'user_id' => $user->id,
    ]);

    expect($code->usedByUserCount($user))->toBe(1);
});

it('flatAmount state sets kind correctly', function () {
    $code = DiscountCode::factory()->flatAmount(500, 'EUR')->create();

    expect($code->kind)->toBe(DiscountKind::FlatAmount);
    expect($code->amount_minor)->toBe(500);
    expect($code->currency)->toBe('EUR');
});

it('events_subset state flips applies_to', function () {
    $code = DiscountCode::factory()->scopedToEventsSubset()->create();

    expect($code->applies_to)->toBe(DiscountScope::EventsSubset);
});

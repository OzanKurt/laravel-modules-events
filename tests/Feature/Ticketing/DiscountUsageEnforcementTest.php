<?php

declare(strict_types=1);

use Kurt\Modules\Events\Support\Events as EventsService;
use Kurt\Modules\Events\Tests\Stubs\StubUser;
use Kurt\Modules\Events\Ticketing\Enums\DiscountKind;
use Kurt\Modules\Events\Ticketing\Exceptions\DiscountCodeNotApplicable;
use Kurt\Modules\Events\Ticketing\Models\DiscountCode;
use Kurt\Modules\Events\Ticketing\Models\DiscountCodeUsage;
use Kurt\Modules\Events\Ticketing\Models\TicketType;

beforeEach(function () {
    config()->set('kurtmodules.user_model', StubUser::class);
});

it('records a usage row and blocks a code once max_uses_total is reached', function () {
    $events = app(EventsService::class);
    $type = TicketType::factory()->create(['price_minor' => 1000, 'currency' => 'USD', 'capacity' => 100]);
    $code = DiscountCode::factory()->create([
        'code' => 'ONCE',
        'kind' => DiscountKind::Percent,
        'amount_minor' => 1000,
        'max_uses_total' => 1,
        'uses_count' => 0,
        'active' => true,
    ]);

    $buyer1 = StubUser::create(['email' => 'b1@x.com']);
    $buyer2 = StubUser::create(['email' => 'b2@x.com']);

    $events->reserve($type, $buyer1, 1, [['name' => 'A', 'email' => 'a@x.com']], 'ONCE');

    expect(DiscountCodeUsage::query()->where('discount_code_id', $code->id)->count())->toBe(1);
    expect($code->fresh()->uses_count)->toBe(1);

    expect(fn () => $events->reserve($type, $buyer2, 1, [['name' => 'B', 'email' => 'b@x.com']], 'ONCE'))
        ->toThrow(DiscountCodeNotApplicable::class);

    // The rejected reservation must not have left a usage row behind.
    expect(DiscountCodeUsage::query()->where('discount_code_id', $code->id)->count())->toBe(1);
    expect($code->fresh()->uses_count)->toBe(1);
});

it('enforces the per-user usage limit', function () {
    $events = app(EventsService::class);
    $type = TicketType::factory()->create(['price_minor' => 1000, 'currency' => 'USD', 'capacity' => 100]);
    DiscountCode::factory()->create([
        'code' => 'PERUSER',
        'kind' => DiscountKind::Percent,
        'amount_minor' => 1000,
        'max_uses_per_user' => 1,
        'uses_count' => 0,
    ]);

    $buyer = StubUser::create(['email' => 'b@x.com']);

    $events->reserve($type, $buyer, 1, [['name' => 'A', 'email' => 'a@x.com']], 'PERUSER');

    expect(fn () => $events->reserve($type, $buyer, 1, [['name' => 'A2', 'email' => 'a2@x.com']], 'PERUSER'))
        ->toThrow(DiscountCodeNotApplicable::class);
});

it('holds the max_uses_total cap under a tight reservation loop', function () {
    $events = app(EventsService::class);
    $type = TicketType::factory()->create(['price_minor' => 1000, 'currency' => 'USD', 'capacity' => 100]);
    DiscountCode::factory()->create([
        'code' => 'CAP3',
        'kind' => DiscountKind::Percent,
        'amount_minor' => 1000,
        'max_uses_total' => 3,
        'uses_count' => 0,
    ]);

    $succeeded = 0;
    for ($i = 0; $i < 10; $i++) {
        $buyer = StubUser::create(['email' => "loop{$i}@x.com"]);
        try {
            $events->reserve($type, $buyer, 1, [['name' => "H{$i}", 'email' => "h{$i}@x.com"]], 'CAP3');
            $succeeded++;
        } catch (DiscountCodeNotApplicable) {
            // expected once the cap is hit
        }
    }

    expect($succeeded)->toBe(3);
    expect(DiscountCode::query()->where('code', 'CAP3')->firstOrFail()->uses_count)->toBe(3);
    expect(DiscountCodeUsage::query()->count())->toBe(3);
});

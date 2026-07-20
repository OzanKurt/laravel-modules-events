<?php

declare(strict_types=1);

use Kurt\Modules\Events\Support\Events as EventsService;
use Kurt\Modules\Events\Tests\Stubs\StubUser;
use Kurt\Modules\Events\Ticketing\Models\Order;
use Kurt\Modules\Events\Ticketing\Models\ReferralLink;

beforeEach(function () {
    config()->set('kurtmodules.user_model', StubUser::class);
});

it('does not attribute a referral once max_uses is reached', function () {
    $order = Order::factory()->create();
    $organizer = StubUser::create(['email' => 'o@x.com']);
    $link = ReferralLink::factory()->create([
        'event_id' => $order->event_id,
        'organizer_id' => $organizer->id,
        'code' => 'MAX1',
        'active' => true,
        'max_uses' => 1,
        'uses_count' => 1,
    ]);

    app(EventsService::class)->attributeReferral($order, 'MAX1');

    expect($order->fresh()->referral_link_id)->toBeNull();
    expect($link->fresh()->uses_count)->toBe(1);
});

it('does not attribute an expired referral link', function () {
    $order = Order::factory()->create();
    $organizer = StubUser::create(['email' => 'o@x.com']);
    $link = ReferralLink::factory()->create([
        'event_id' => $order->event_id,
        'organizer_id' => $organizer->id,
        'code' => 'GONE',
        'active' => true,
        'max_uses' => null,
        'uses_count' => 0,
        'expires_at' => now()->subDay(),
    ]);

    app(EventsService::class)->attributeReferral($order, 'GONE');

    expect($order->fresh()->referral_link_id)->toBeNull();
    expect($link->fresh()->uses_count)->toBe(0);
});

it('attributes up to max_uses across a tight loop then stops', function () {
    $organizer = StubUser::create(['email' => 'o@x.com']);
    $link = ReferralLink::factory()->create([
        'organizer_id' => $organizer->id,
        'event_id' => null,
        'code' => 'CAP2',
        'active' => true,
        'max_uses' => 2,
        'uses_count' => 0,
    ]);

    $attributed = 0;
    foreach (range(1, 4) as $i) {
        $order = Order::factory()->create();
        app(EventsService::class)->attributeReferral($order, 'CAP2');
        if ($order->fresh()->referral_link_id !== null) {
            $attributed++;
        }
    }

    expect($attributed)->toBe(2);
    expect($link->fresh()->uses_count)->toBe(2);
});

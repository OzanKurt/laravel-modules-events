<?php

declare(strict_types=1);

use Kurt\Modules\Events\Support\Events as EventsService;
use Kurt\Modules\Events\Tests\Stubs\StubUser;
use Kurt\Modules\Events\Ticketing\Exceptions\PriceTierSoldOut;
use Kurt\Modules\Events\Ticketing\Models\PriceTier;
use Kurt\Modules\Events\Ticketing\Models\TicketType;

beforeEach(function () {
    config()->set('kurtmodules.user_model', StubUser::class);
});

/**
 * @return array<int, array{name: string, email: string}>
 */
function tierAssignments(int $count): array
{
    $out = [];
    for ($i = 0; $i < $count; $i++) {
        $out[] = ['name' => "H{$i}", 'email' => "h{$i}@x.com"];
    }

    return $out;
}

it('rejects a reservation that would oversell a price tier', function () {
    $type = TicketType::factory()->create(['price_minor' => 1000, 'currency' => 'USD', 'capacity' => null]);
    PriceTier::factory()->create([
        'ticket_type_id' => $type->id,
        'capacity' => 2,
        'sold_count' => 0,
        'price_minor' => 1000,
        'starts_at' => null,
        'ends_at' => null,
        'position' => 0,
    ]);
    $buyer = StubUser::create(['email' => 'b@x.com']);

    expect(fn () => app(EventsService::class)->reserve($type, $buyer, 3, tierAssignments(3)))
        ->toThrow(PriceTierSoldOut::class);
});

it('increments tier sold_count and blocks further reservations at capacity', function () {
    $type = TicketType::factory()->create(['price_minor' => 1000, 'currency' => 'USD', 'capacity' => null]);
    $tier = PriceTier::factory()->create([
        'ticket_type_id' => $type->id,
        'capacity' => 2,
        'sold_count' => 0,
        'price_minor' => 1000,
        'starts_at' => null,
        'ends_at' => null,
        'position' => 0,
    ]);

    $buyer1 = StubUser::create(['email' => 'b1@x.com']);
    app(EventsService::class)->reserve($type, $buyer1, 2, tierAssignments(2));
    expect($tier->fresh()->sold_count)->toBe(2);

    $buyer2 = StubUser::create(['email' => 'b2@x.com']);
    expect(fn () => app(EventsService::class)->reserve($type, $buyer2, 1, tierAssignments(1)))
        ->toThrow(PriceTierSoldOut::class);
});

it('releases tier capacity when a pending order expires', function () {
    config()->set('events.orders.pending_timeout_minutes', 15);

    $type = TicketType::factory()->create(['price_minor' => 1000, 'currency' => 'USD', 'capacity' => null]);
    $tier = PriceTier::factory()->create([
        'ticket_type_id' => $type->id,
        'capacity' => 2,
        'sold_count' => 0,
        'price_minor' => 1000,
        'starts_at' => null,
        'ends_at' => null,
        'position' => 0,
    ]);

    $buyer = StubUser::create(['email' => 'b@x.com']);
    app(EventsService::class)->reserve($type, $buyer, 2, tierAssignments(2));
    expect($tier->fresh()->sold_count)->toBe(2);

    // Advance past the cart timeout so the sweep cancels the order.
    $this->travel(20)->minutes();
    $this->artisan('events:expire-pending-orders')->assertSuccessful();

    expect($tier->fresh()->sold_count)->toBe(0);
    expect($type->fresh()->sold_count)->toBe(0);
});

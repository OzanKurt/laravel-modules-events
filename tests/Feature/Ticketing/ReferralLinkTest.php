<?php

declare(strict_types=1);

use Kurt\Modules\Events\Ticketing\Models\ReferralLink;

it('creates an active link with code + commission', function () {
    $link = ReferralLink::factory()->create([
        'code' => 'PROMO1234',
        'commission_basis_points' => 750,
    ]);

    expect($link->active)->toBeTrue();
    expect($link->commission_basis_points)->toBe(750);
    expect($link->code)->toBe('PROMO1234');
});

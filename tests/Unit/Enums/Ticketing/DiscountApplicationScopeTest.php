<?php

declare(strict_types=1);

use Kurt\Modules\Events\Ticketing\Enums\DiscountApplicationScope;

it('has expected cases and values', function () {
    expect(DiscountApplicationScope::Order->value)->toBe('order');
    expect(DiscountApplicationScope::PerTicket->value)->toBe('per_ticket');
});

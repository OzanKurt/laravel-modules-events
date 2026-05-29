<?php

declare(strict_types=1);

use Kurt\Modules\Events\Ticketing\Enums\OrderStatus;

it('has expected cases and values', function () {
    expect(OrderStatus::Pending->value)->toBe('pending');
    expect(OrderStatus::Paid->value)->toBe('paid');
    expect(OrderStatus::Cancelled->value)->toBe('cancelled');
    expect(OrderStatus::Refunded->value)->toBe('refunded');
    expect(OrderStatus::PartiallyRefunded->value)->toBe('partially_refunded');
});

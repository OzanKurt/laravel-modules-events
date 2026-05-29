<?php

declare(strict_types=1);

use Kurt\Modules\Events\Ticketing\Enums\DiscountKind;

it('has expected cases and values', function () {
    expect(DiscountKind::Percent->value)->toBe('percent');
    expect(DiscountKind::FlatAmount->value)->toBe('flat_amount');
});

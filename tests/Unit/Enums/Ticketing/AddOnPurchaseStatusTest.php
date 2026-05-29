<?php

declare(strict_types=1);

use Kurt\Modules\Events\Ticketing\Enums\AddOnPurchaseStatus;

it('has expected cases and values', function () {
    expect(AddOnPurchaseStatus::Pending->value)->toBe('pending');
    expect(AddOnPurchaseStatus::Paid->value)->toBe('paid');
    expect(AddOnPurchaseStatus::Cancelled->value)->toBe('cancelled');
    expect(AddOnPurchaseStatus::Refunded->value)->toBe('refunded');
});

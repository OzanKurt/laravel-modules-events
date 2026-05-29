<?php

declare(strict_types=1);

use Kurt\Modules\Events\Flow\Enums\PayoutStatus;

it('has expected cases and values', function () {
    expect(PayoutStatus::Accrued->value)->toBe('accrued');
    expect(PayoutStatus::PaidOut->value)->toBe('paid_out');
    expect(PayoutStatus::Reversed->value)->toBe('reversed');
});

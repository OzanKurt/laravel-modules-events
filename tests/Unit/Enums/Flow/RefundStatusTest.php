<?php

declare(strict_types=1);

use Kurt\Modules\Events\Flow\Enums\RefundStatus;

it('has expected cases and values', function () {
    expect(RefundStatus::Pending->value)->toBe('pending');
    expect(RefundStatus::Processed->value)->toBe('processed');
    expect(RefundStatus::Failed->value)->toBe('failed');
});

<?php

declare(strict_types=1);

use Kurt\Modules\Events\Eligibility\Enums\CheckStatus;

it('has expected cases and values', function () {
    expect(CheckStatus::Pending->value)->toBe('pending');
    expect(CheckStatus::Passed->value)->toBe('passed');
    expect(CheckStatus::Failed->value)->toBe('failed');
    expect(CheckStatus::Waived->value)->toBe('waived');
});

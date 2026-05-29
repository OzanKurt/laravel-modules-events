<?php

declare(strict_types=1);

use Kurt\Modules\Events\Flow\Enums\WaitlistStatus;

it('has expected cases and values', function () {
    expect(WaitlistStatus::Waiting->value)->toBe('waiting');
    expect(WaitlistStatus::Offered->value)->toBe('offered');
    expect(WaitlistStatus::Claimed->value)->toBe('claimed');
    expect(WaitlistStatus::Expired->value)->toBe('expired');
});

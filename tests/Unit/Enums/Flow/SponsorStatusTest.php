<?php

declare(strict_types=1);

use Kurt\Modules\Events\Flow\Enums\SponsorStatus;

it('has expected cases and values', function () {
    expect(SponsorStatus::Pending->value)->toBe('pending');
    expect(SponsorStatus::Active->value)->toBe('active');
    expect(SponsorStatus::Cancelled->value)->toBe('cancelled');
});

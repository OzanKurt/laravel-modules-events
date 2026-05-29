<?php

declare(strict_types=1);

use Kurt\Modules\Events\Flow\Enums\QueueStatus;

it('has expected cases and values', function () {
    expect(QueueStatus::Waiting->value)->toBe('waiting');
    expect(QueueStatus::Active->value)->toBe('active');
    expect(QueueStatus::Expired->value)->toBe('expired');
    expect(QueueStatus::Completed->value)->toBe('completed');
    expect(QueueStatus::Abandoned->value)->toBe('abandoned');
});

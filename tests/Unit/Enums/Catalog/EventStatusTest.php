<?php

declare(strict_types=1);

use Kurt\Modules\Events\Catalog\Enums\EventStatus;

it('has expected cases and values', function () {
    expect(EventStatus::Draft->value)->toBe('draft');
    expect(EventStatus::PendingApproval->value)->toBe('pending_approval');
    expect(EventStatus::Published->value)->toBe('published');
    expect(EventStatus::Cancelled->value)->toBe('cancelled');
    expect(EventStatus::Completed->value)->toBe('completed');
});

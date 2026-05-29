<?php

declare(strict_types=1);

use Kurt\Modules\Events\Catalog\Enums\EventVisibility;

it('has expected cases and values', function () {
    expect(EventVisibility::Public->value)->toBe('public');
    expect(EventVisibility::Unlisted->value)->toBe('unlisted');
    expect(EventVisibility::Private->value)->toBe('private');
});

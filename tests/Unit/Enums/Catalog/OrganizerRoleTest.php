<?php

declare(strict_types=1);

use Kurt\Modules\Events\Catalog\Enums\OrganizerRole;

it('has expected cases and values', function () {
    expect(OrganizerRole::Owner->value)->toBe('owner');
    expect(OrganizerRole::Manager->value)->toBe('manager');
    expect(OrganizerRole::Scanner->value)->toBe('scanner');
});

<?php

declare(strict_types=1);

use Kurt\Modules\Events\Ticketing\Enums\DiscountScope;

it('has expected cases and values', function () {
    expect(DiscountScope::Global->value)->toBe('global');
    expect(DiscountScope::EventsSubset->value)->toBe('events_subset');
});

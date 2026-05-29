<?php

declare(strict_types=1);

use Kurt\Modules\Events\Catalog\Enums\RecurrenceFrequency;

it('has expected cases and values', function () {
    expect(RecurrenceFrequency::None->value)->toBe('none');
    expect(RecurrenceFrequency::Daily->value)->toBe('daily');
    expect(RecurrenceFrequency::Weekly->value)->toBe('weekly');
    expect(RecurrenceFrequency::Monthly->value)->toBe('monthly');
    expect(RecurrenceFrequency::Yearly->value)->toBe('yearly');
});

<?php

declare(strict_types=1);

use Kurt\Modules\Events\Catalog\Enums\AttendeeListVisibility;

it('has expected cases and values', function () {
    expect(AttendeeListVisibility::Private->value)->toBe('private');
    expect(AttendeeListVisibility::OrganizerOnly->value)->toBe('organizer_only');
    expect(AttendeeListVisibility::AttendeesOnly->value)->toBe('attendees_only');
    expect(AttendeeListVisibility::Public->value)->toBe('public');
});

it('ranks visibility from most restrictive to most open', function () {
    expect(AttendeeListVisibility::Private->rank())->toBe(0);
    expect(AttendeeListVisibility::OrganizerOnly->rank())->toBe(1);
    expect(AttendeeListVisibility::AttendeesOnly->rank())->toBe(2);
    expect(AttendeeListVisibility::Public->rank())->toBe(3);
});

it('detects when one visibility is more restrictive than another', function () {
    expect(AttendeeListVisibility::Private->isMoreRestrictiveThan(AttendeeListVisibility::Public))->toBeTrue();
    expect(AttendeeListVisibility::OrganizerOnly->isMoreRestrictiveThan(AttendeeListVisibility::Public))->toBeTrue();
    expect(AttendeeListVisibility::Public->isMoreRestrictiveThan(AttendeeListVisibility::Private))->toBeFalse();
    expect(AttendeeListVisibility::Public->isMoreRestrictiveThan(AttendeeListVisibility::Public))->toBeFalse();
});

<?php

declare(strict_types=1);

use Kurt\Modules\Events\Flow\Enums\RefundReason;

it('has expected cases and values', function () {
    expect(RefundReason::Rejection->value)->toBe('rejection');
    expect(RefundReason::CancelledEvent->value)->toBe('cancelled_event');
    expect(RefundReason::AttendeeRequest->value)->toBe('attendee_request');
    expect(RefundReason::OrganizerInitiated->value)->toBe('organizer_initiated');
    expect(RefundReason::Other->value)->toBe('other');
});

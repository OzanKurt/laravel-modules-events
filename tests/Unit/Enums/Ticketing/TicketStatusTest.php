<?php

declare(strict_types=1);

use Kurt\Modules\Events\Ticketing\Enums\TicketStatus;

it('has expected cases and values', function () {
    expect(TicketStatus::Issued->value)->toBe('issued');
    expect(TicketStatus::Cancelled->value)->toBe('cancelled');
    expect(TicketStatus::Refunded->value)->toBe('refunded');
    expect(TicketStatus::CheckedIn->value)->toBe('checked_in');
    expect(TicketStatus::Transferred->value)->toBe('transferred');
});

<?php

declare(strict_types=1);

use Kurt\Modules\Events\Ticketing\Enums\TicketTypeMode;

it('has expected cases and values', function () {
    expect(TicketTypeMode::Open->value)->toBe('open');
    expect(TicketTypeMode::Application->value)->toBe('application');
    expect(TicketTypeMode::Rsvp->value)->toBe('rsvp');
});

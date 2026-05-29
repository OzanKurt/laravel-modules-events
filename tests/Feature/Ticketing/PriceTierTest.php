<?php

declare(strict_types=1);

use Kurt\Modules\Events\Ticketing\Models\PriceTier;
use Kurt\Modules\Events\Ticketing\Models\TicketType;

it('belongs to ticket type', function () {
    $type = TicketType::factory()->create();
    $tier = PriceTier::factory()->create(['ticket_type_id' => $type->id]);

    expect($tier->ticketType->id)->toBe($type->id);
});

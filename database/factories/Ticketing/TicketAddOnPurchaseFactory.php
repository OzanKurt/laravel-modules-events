<?php

declare(strict_types=1);

namespace Database\Factories\Kurt\Modules\Events\Ticketing;

use Illuminate\Database\Eloquent\Factories\Factory;
use Kurt\Modules\Events\Ticketing\Enums\AddOnPurchaseStatus;
use Kurt\Modules\Events\Ticketing\Models\OrderItem;
use Kurt\Modules\Events\Ticketing\Models\Ticket;
use Kurt\Modules\Events\Ticketing\Models\TicketAddOn;
use Kurt\Modules\Events\Ticketing\Models\TicketAddOnPurchase;

/**
 * @extends Factory<TicketAddOnPurchase>
 */
class TicketAddOnPurchaseFactory extends Factory
{
    /** @var class-string<TicketAddOnPurchase> */
    protected $model = TicketAddOnPurchase::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'ticket_id' => Ticket::factory(),
            'add_on_id' => TicketAddOn::factory(),
            'order_item_id' => OrderItem::factory(),
            'quantity' => 1,
            'unit_price_minor' => 500,
            'line_total_minor' => 500,
            'status' => AddOnPurchaseStatus::Pending,
        ];
    }
}

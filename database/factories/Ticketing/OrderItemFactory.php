<?php

declare(strict_types=1);

namespace Database\Factories\Kurt\Modules\Events\Ticketing;

use Illuminate\Database\Eloquent\Factories\Factory;
use Kurt\Modules\Events\Ticketing\Models\Order;
use Kurt\Modules\Events\Ticketing\Models\OrderItem;
use Kurt\Modules\Events\Ticketing\Models\TicketType;

/**
 * @extends Factory<OrderItem>
 */
class OrderItemFactory extends Factory
{
    /** @var class-string<OrderItem> */
    protected $model = OrderItem::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'ticket_type_id' => TicketType::factory(),
            'quantity' => 1,
            'unit_price_minor' => 1000,
            'line_total_minor' => 1000,
        ];
    }
}

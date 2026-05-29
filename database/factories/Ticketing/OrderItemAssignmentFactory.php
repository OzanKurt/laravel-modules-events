<?php

declare(strict_types=1);

namespace Database\Factories\Kurt\Modules\Events\Ticketing;

use Illuminate\Database\Eloquent\Factories\Factory;
use Kurt\Modules\Events\Ticketing\Models\OrderItem;
use Kurt\Modules\Events\Ticketing\Models\OrderItemAssignment;

/**
 * @extends Factory<OrderItemAssignment>
 */
class OrderItemAssignmentFactory extends Factory
{
    /** @var class-string<OrderItemAssignment> */
    protected $model = OrderItemAssignment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_item_id' => OrderItem::factory(),
            'seat_index' => 0,
            'holder_name' => $this->faker->name(),
            'holder_email' => $this->faker->safeEmail(),
        ];
    }
}

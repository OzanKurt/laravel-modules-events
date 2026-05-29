<?php

declare(strict_types=1);

namespace Database\Factories\Kurt\Modules\Events\Ticketing;

use Illuminate\Database\Eloquent\Factories\Factory;
use Kurt\Modules\Events\Catalog\Models\Event;
use Kurt\Modules\Events\Ticketing\Enums\OrderStatus;
use Kurt\Modules\Events\Ticketing\Models\Order;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /** @var class-string<Order> */
    protected $model = Order::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'user_id' => 1,
            'status' => OrderStatus::Pending,
            'subtotal_minor' => 1000,
            'discount_minor' => 0,
            'tax_minor' => 0,
            'total_minor' => 1000,
            'currency' => 'USD',
        ];
    }

    public function paid(): static
    {
        return $this->state(fn () => [
            'status' => OrderStatus::Paid,
            'paid_at' => now(),
        ]);
    }
}

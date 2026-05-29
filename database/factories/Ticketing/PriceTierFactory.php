<?php

declare(strict_types=1);

namespace Database\Factories\Kurt\Modules\Events\Ticketing;

use Illuminate\Database\Eloquent\Factories\Factory;
use Kurt\Modules\Events\Ticketing\Models\PriceTier;
use Kurt\Modules\Events\Ticketing\Models\TicketType;

/**
 * @extends Factory<PriceTier>
 */
class PriceTierFactory extends Factory
{
    /** @var class-string<PriceTier> */
    protected $model = PriceTier::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'ticket_type_id' => TicketType::factory(),
            'name' => $this->faker->randomElement(['Early bird', 'Regular', 'Last minute']),
            'price_minor' => $this->faker->numberBetween(500, 10000),
            'position' => 0,
            'sold_count' => 0,
        ];
    }
}

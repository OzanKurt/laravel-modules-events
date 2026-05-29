<?php

declare(strict_types=1);

namespace Database\Factories\Kurt\Modules\Events\Ticketing;

use Illuminate\Database\Eloquent\Factories\Factory;
use Kurt\Modules\Events\Catalog\Models\Event;
use Kurt\Modules\Events\Ticketing\Models\TicketAddOn;

/**
 * @extends Factory<TicketAddOn>
 */
class TicketAddOnFactory extends Factory
{
    /** @var class-string<TicketAddOn> */
    protected $model = TicketAddOn::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->unique()->words(2, true);

        return [
            'event_id' => Event::factory(),
            'slug' => str($name)->slug()->toString(),
            'name' => ['en' => $name],
            'price_minor' => 500,
            'currency' => 'USD',
            'scannable' => false,
            'position' => 0,
            'active' => true,
            'sold_count' => 0,
        ];
    }

    public function scannable(): static
    {
        return $this->state(fn () => ['scannable' => true]);
    }
}

<?php

declare(strict_types=1);

namespace Database\Factories\Kurt\Modules\Events\Ticketing;

use Illuminate\Database\Eloquent\Factories\Factory;
use Kurt\Modules\Events\Catalog\Models\Event;
use Kurt\Modules\Events\Ticketing\Enums\TicketTypeMode;
use Kurt\Modules\Events\Ticketing\Models\TicketType;

/**
 * @extends Factory<TicketType>
 */
class TicketTypeFactory extends Factory
{
    /** @var class-string<TicketType> */
    protected $model = TicketType::class;

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
            'mode' => TicketTypeMode::Open,
            'price_minor' => 1000,
            'currency' => 'USD',
            'refundable' => true,
            'transferable' => true,
            'consumer_protection_exempt' => false,
            'max_per_order' => 10,
            'position' => 0,
        ];
    }

    public function rsvp(): static
    {
        return $this->state(fn () => ['mode' => TicketTypeMode::Rsvp, 'price_minor' => 0]);
    }

    public function application(): static
    {
        return $this->state(fn () => ['mode' => TicketTypeMode::Application]);
    }

    public function nontransferable(): static
    {
        return $this->state(fn () => ['transferable' => false]);
    }
}

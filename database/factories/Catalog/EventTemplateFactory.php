<?php

declare(strict_types=1);

namespace Database\Factories\Kurt\Modules\Events\Catalog;

use Illuminate\Database\Eloquent\Factories\Factory;
use Kurt\Modules\Events\Catalog\Models\EventTemplate;

/**
 * @extends Factory<EventTemplate>
 */
class EventTemplateFactory extends Factory
{
    /** @var class-string<EventTemplate> */
    protected $model = EventTemplate::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->unique()->sentence(3);

        return [
            'owner_id' => 1,
            'slug' => str($name)->slug()->toString(),
            'name' => $name,
            'description' => $this->faker->sentence(),
            'payload' => ['title' => $name, 'sessions' => []],
            'is_public' => false,
            'used_count' => 0,
        ];
    }

    public function public(): static
    {
        return $this->state(fn () => ['is_public' => true]);
    }
}

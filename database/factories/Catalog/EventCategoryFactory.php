<?php

declare(strict_types=1);

namespace Database\Factories\Kurt\Modules\Events\Catalog;

use Illuminate\Database\Eloquent\Factories\Factory;
use Kurt\Modules\Events\Catalog\Models\EventCategory;

/**
 * @extends Factory<EventCategory>
 */
class EventCategoryFactory extends Factory
{
    /** @var class-string<EventCategory> */
    protected $model = EventCategory::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->unique()->words(2, true);

        return [
            'slug' => str($name)->slug()->toString(),
            'name' => ['en' => $name],
            'position' => 0,
        ];
    }
}

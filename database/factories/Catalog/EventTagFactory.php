<?php

declare(strict_types=1);

namespace Database\Factories\Kurt\Modules\Events\Catalog;

use Illuminate\Database\Eloquent\Factories\Factory;
use Kurt\Modules\Events\Catalog\Models\EventTag;

/**
 * @extends Factory<EventTag>
 */
class EventTagFactory extends Factory
{
    /** @var class-string<EventTag> */
    protected $model = EventTag::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->unique()->word();

        return [
            'slug' => str($name)->slug()->toString(),
            'name' => ['en' => $name],
        ];
    }
}

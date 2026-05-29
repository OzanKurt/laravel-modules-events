<?php

declare(strict_types=1);

namespace Database\Factories\Kurt\Modules\Events\Catalog;

use Illuminate\Database\Eloquent\Factories\Factory;
use Kurt\Modules\Events\Catalog\Models\Event;
use Kurt\Modules\Events\Catalog\Models\Session;

/**
 * @extends Factory<Session>
 */
class SessionFactory extends Factory
{
    /** @var class-string<Session> */
    protected $model = Session::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = $this->faker->unique()->sentence(3);

        return [
            'event_id' => Event::factory(),
            'slug' => str($title)->slug()->toString(),
            'title' => ['en' => $title],
            'starts_at' => now()->addDays(30),
            'ends_at' => now()->addDays(30)->addHours(1),
            'position' => 0,
        ];
    }
}

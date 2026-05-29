<?php

declare(strict_types=1);

namespace Database\Factories\Kurt\Modules\Events\Catalog;

use Illuminate\Database\Eloquent\Factories\Factory;
use Kurt\Modules\Events\Catalog\Enums\AttendeeListVisibility;
use Kurt\Modules\Events\Catalog\Enums\EventStatus;
use Kurt\Modules\Events\Catalog\Enums\EventVisibility;
use Kurt\Modules\Events\Catalog\Models\Event;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
{
    /** @var class-string<Event> */
    protected $model = Event::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = $this->faker->unique()->sentence(4);

        return [
            'slug' => str($title)->slug()->toString(),
            'title' => ['en' => $title],
            'status' => EventStatus::Draft,
            'visibility' => EventVisibility::Public,
            'attendee_list_visibility' => AttendeeListVisibility::OrganizerOnly,
            'starts_at' => now()->addDays(30),
            'ends_at' => now()->addDays(30)->addHours(2),
            'timezone' => 'UTC',
        ];
    }

    public function published(): static
    {
        return $this->state(fn () => ['status' => EventStatus::Published]);
    }

    public function upcoming(): static
    {
        return $this->state(fn () => [
            'starts_at' => now()->addDays(7),
            'ends_at' => now()->addDays(7)->addHours(2),
        ]);
    }

    public function past(): static
    {
        return $this->state(fn () => [
            'starts_at' => now()->subDays(7),
            'ends_at' => now()->subDays(7)->addHours(2),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn () => [
            'status' => EventStatus::Cancelled,
            'cancelled_at' => now(),
        ]);
    }
}

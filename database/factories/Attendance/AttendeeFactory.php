<?php

declare(strict_types=1);

namespace Database\Factories\Kurt\Modules\Events\Attendance;

use Illuminate\Database\Eloquent\Factories\Factory;
use Kurt\Modules\Events\Attendance\Enums\AttendeeStatus;
use Kurt\Modules\Events\Attendance\Models\Attendee;
use Kurt\Modules\Events\Catalog\Models\Event;
use Kurt\Modules\Events\Ticketing\Models\Ticket;

/**
 * @extends Factory<Attendee>
 */
class AttendeeFactory extends Factory
{
    /** @var class-string<Attendee> */
    protected $model = Attendee::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'ticket_id' => Ticket::factory(),
            'user_id' => 1,
            'status' => AttendeeStatus::Registered,
            'profile' => ['name' => $this->faker->name(), 'email' => $this->faker->safeEmail()],
        ];
    }
}

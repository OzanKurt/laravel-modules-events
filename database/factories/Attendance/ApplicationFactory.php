<?php

declare(strict_types=1);

namespace Database\Factories\Kurt\Modules\Events\Attendance;

use Illuminate\Database\Eloquent\Factories\Factory;
use Kurt\Modules\Events\Attendance\Enums\ApplicationStatus;
use Kurt\Modules\Events\Attendance\Models\Application;
use Kurt\Modules\Events\Catalog\Models\Event;
use Kurt\Modules\Events\Ticketing\Models\TicketType;

/**
 * @extends Factory<Application>
 */
class ApplicationFactory extends Factory
{
    /** @var class-string<Application> */
    protected $model = Application::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'ticket_type_id' => TicketType::factory(),
            'applicant_id' => 1,
            'status' => ApplicationStatus::Pending,
            'submitted_at' => now(),
        ];
    }

    public function approved(): static
    {
        return $this->state(fn () => [
            'status' => ApplicationStatus::Approved,
            'decided_at' => now(),
        ]);
    }
}

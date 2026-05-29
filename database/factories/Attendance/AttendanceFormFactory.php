<?php

declare(strict_types=1);

namespace Database\Factories\Kurt\Modules\Events\Attendance;

use Illuminate\Database\Eloquent\Factories\Factory;
use Kurt\Modules\Events\Attendance\Models\AttendanceForm;
use Kurt\Modules\Events\Catalog\Models\Event;

/**
 * @extends Factory<AttendanceForm>
 */
class AttendanceFormFactory extends Factory
{
    /** @var class-string<AttendanceForm> */
    protected $model = AttendanceForm::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'name' => $this->faker->sentence(3),
            'schema' => [
                ['key' => 'dietary', 'label' => 'Dietary requirements', 'type' => 'text', 'required' => false],
            ],
        ];
    }
}

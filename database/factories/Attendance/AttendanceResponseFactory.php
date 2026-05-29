<?php

declare(strict_types=1);

namespace Database\Factories\Kurt\Modules\Events\Attendance;

use Illuminate\Database\Eloquent\Factories\Factory;
use Kurt\Modules\Events\Attendance\Models\AttendanceForm;
use Kurt\Modules\Events\Attendance\Models\AttendanceResponse;
use Kurt\Modules\Events\Attendance\Models\Attendee;

/**
 * @extends Factory<AttendanceResponse>
 */
class AttendanceResponseFactory extends Factory
{
    /** @var class-string<AttendanceResponse> */
    protected $model = AttendanceResponse::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'attendee_id' => Attendee::factory(),
            'attendance_form_id' => AttendanceForm::factory(),
            'answers' => ['dietary' => 'vegetarian'],
        ];
    }
}

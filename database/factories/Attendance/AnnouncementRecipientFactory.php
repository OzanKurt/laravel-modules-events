<?php

declare(strict_types=1);

namespace Database\Factories\Kurt\Modules\Events\Attendance;

use Illuminate\Database\Eloquent\Factories\Factory;
use Kurt\Modules\Events\Attendance\Enums\AnnouncementRecipientStatus;
use Kurt\Modules\Events\Attendance\Models\Announcement;
use Kurt\Modules\Events\Attendance\Models\AnnouncementRecipient;
use Kurt\Modules\Events\Attendance\Models\Attendee;

/**
 * @extends Factory<AnnouncementRecipient>
 */
class AnnouncementRecipientFactory extends Factory
{
    /** @var class-string<AnnouncementRecipient> */
    protected $model = AnnouncementRecipient::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'announcement_id' => Announcement::factory(),
            'attendee_id' => Attendee::factory(),
            'status' => AnnouncementRecipientStatus::Pending,
        ];
    }
}

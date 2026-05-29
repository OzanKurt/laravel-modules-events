<?php

declare(strict_types=1);

namespace Database\Factories\Kurt\Modules\Events\Attendance;

use Illuminate\Database\Eloquent\Factories\Factory;
use Kurt\Modules\Events\Attendance\Enums\AnnouncementAudience;
use Kurt\Modules\Events\Attendance\Models\Announcement;
use Kurt\Modules\Events\Catalog\Models\Event;

/**
 * @extends Factory<Announcement>
 */
class AnnouncementFactory extends Factory
{
    /** @var class-string<Announcement> */
    protected $model = Announcement::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'author_id' => 1,
            'subject' => $this->faker->sentence(),
            'body' => $this->faker->paragraph(),
            'audience' => AnnouncementAudience::All,
        ];
    }
}

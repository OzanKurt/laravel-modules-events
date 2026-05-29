<?php

declare(strict_types=1);

namespace Database\Factories\Kurt\Modules\Events\Catalog;

use Illuminate\Database\Eloquent\Factories\Factory;
use Kurt\Modules\Events\Catalog\Enums\OrganizerRole;
use Kurt\Modules\Events\Catalog\Models\Event;
use Kurt\Modules\Events\Catalog\Models\EventOrganizer;

/**
 * @extends Factory<EventOrganizer>
 */
class EventOrganizerFactory extends Factory
{
    /** @var class-string<EventOrganizer> */
    protected $model = EventOrganizer::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'user_id' => 1,
            'role' => OrganizerRole::Owner,
            'commission_basis_points' => null,
        ];
    }
}

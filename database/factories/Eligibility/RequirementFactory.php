<?php

declare(strict_types=1);

namespace Database\Factories\Kurt\Modules\Events\Eligibility;

use Illuminate\Database\Eloquent\Factories\Factory;
use Kurt\Modules\Events\Catalog\Models\Event;
use Kurt\Modules\Events\Eligibility\Enums\RequirementType;
use Kurt\Modules\Events\Eligibility\Models\Requirement;

/**
 * @extends Factory<Requirement>
 */
class RequirementFactory extends Factory
{
    /** @var class-string<Requirement> */
    protected $model = Requirement::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'type' => RequirementType::AgeMin,
            'payload' => ['min' => 18],
            'strict' => true,
            'position' => 0,
        ];
    }
}

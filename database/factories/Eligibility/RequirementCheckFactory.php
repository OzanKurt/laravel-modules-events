<?php

declare(strict_types=1);

namespace Database\Factories\Kurt\Modules\Events\Eligibility;

use Illuminate\Database\Eloquent\Factories\Factory;
use Kurt\Modules\Events\Eligibility\Enums\CheckStatus;
use Kurt\Modules\Events\Eligibility\Models\Requirement;
use Kurt\Modules\Events\Eligibility\Models\RequirementCheck;

/**
 * @extends Factory<RequirementCheck>
 */
class RequirementCheckFactory extends Factory
{
    /** @var class-string<RequirementCheck> */
    protected $model = RequirementCheck::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'requirement_id' => Requirement::factory(),
            'status' => CheckStatus::Pending,
        ];
    }
}

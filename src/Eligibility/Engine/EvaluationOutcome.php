<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Eligibility\Engine;

use Kurt\Modules\Events\Eligibility\Models\RequirementCheck;

final readonly class EvaluationOutcome
{
    /** @param  array<int, RequirementCheck>  $checks */
    public function __construct(
        public bool $allPassed,
        public bool $anyStrictFailed,
        public array $checks,
    ) {}
}

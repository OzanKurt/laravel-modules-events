<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Eligibility\Contracts;

use Illuminate\Database\Eloquent\Model;
use Kurt\Modules\Events\Eligibility\Engine\CheckResult;

interface RequirementEvaluator
{
    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $context
     */
    public function evaluate(Model $attendee, array $payload, array $context = []): CheckResult;
}

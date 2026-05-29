<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Eligibility\Engine;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Container\Container;
use Kurt\Modules\Events\Attendance\Models\Attendee;
use Kurt\Modules\Events\Eligibility\Contracts\RequirementEvaluator;
use Kurt\Modules\Events\Eligibility\Enums\CheckStatus;
use Kurt\Modules\Events\Eligibility\Models\Requirement;
use Kurt\Modules\Events\Eligibility\Models\RequirementCheck;
use Kurt\Modules\Events\Ticketing\Models\TicketType;

final class RequirementEngine
{
    public function __construct(
        private readonly Container $container,
        private readonly Repository $config,
    ) {}

    public function evaluateFor(Attendee $attendee, TicketType $ticketType): EvaluationOutcome
    {
        $requirements = Requirement::query()
            ->where(function ($q) use ($ticketType) {
                $q->where('event_id', $ticketType->event_id)
                    ->orWhere('ticket_type_id', $ticketType->id);
            })
            ->get();

        $checks = [];
        $allPassed = true;
        $anyStrictFailed = false;

        foreach ($requirements as $requirement) {
            $evaluatorClass = $this->config->get("events.requirements.evaluators.{$requirement->type->value}");
            if (! is_string($evaluatorClass) || $evaluatorClass === '') {
                continue;
            }

            $evaluator = $this->container->make($evaluatorClass);
            if (! $evaluator instanceof RequirementEvaluator) {
                continue;
            }

            $result = $evaluator->evaluate(
                $attendee,
                $requirement->payload,
                ['requirement_id' => $requirement->id],
            );

            $resultData = $result->data;
            if ($result->message !== null) {
                $resultData['message'] = $result->message;
            }

            $check = RequirementCheck::query()->updateOrCreate(
                ['attendee_id' => $attendee->id, 'requirement_id' => $requirement->id],
                ['status' => $result->status, 'result' => $resultData],
            );

            $checks[] = $check;

            if ($result->status !== CheckStatus::Passed) {
                $allPassed = false;
            }
            if ($result->status === CheckStatus::Failed && $requirement->strict) {
                $anyStrictFailed = true;
            }
        }

        return new EvaluationOutcome($allPassed, $anyStrictFailed, $checks);
    }
}

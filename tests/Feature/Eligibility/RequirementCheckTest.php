<?php

declare(strict_types=1);

use Kurt\Modules\Events\Attendance\Models\Application;
use Kurt\Modules\Events\Attendance\Models\Attendee;
use Kurt\Modules\Events\Eligibility\Enums\CheckStatus;
use Kurt\Modules\Events\Eligibility\Models\Requirement;
use Kurt\Modules\Events\Eligibility\Models\RequirementCheck;

it('belongs to either attendee or application', function () {
    $req = Requirement::factory()->create();
    $attendee = Attendee::factory()->create();

    $check = RequirementCheck::factory()->create([
        'attendee_id' => $attendee->id,
        'application_id' => null,
        'requirement_id' => $req->id,
        'status' => CheckStatus::Passed,
    ]);

    expect($check->attendee?->id)->toBe($attendee->id);
    expect($check->application)->toBeNull();
});

it('can attach to an application', function () {
    $req = Requirement::factory()->create();
    $app = Application::factory()->create();

    $check = RequirementCheck::factory()->create([
        'attendee_id' => null,
        'application_id' => $app->id,
        'requirement_id' => $req->id,
        'status' => CheckStatus::Waived,
    ]);

    expect($check->application?->id)->toBe($app->id);
    expect($check->status)->toBe(CheckStatus::Waived);
});

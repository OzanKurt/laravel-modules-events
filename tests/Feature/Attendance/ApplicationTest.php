<?php

declare(strict_types=1);

use Kurt\Modules\Events\Attendance\Enums\ApplicationStatus;
use Kurt\Modules\Events\Attendance\Models\Application;

it('creates and casts status', function () {
    $app = Application::factory()->create();

    expect($app->status)->toBe(ApplicationStatus::Pending);
});

it('approved state sets decided_at', function () {
    $app = Application::factory()->approved()->create();

    expect($app->status)->toBe(ApplicationStatus::Approved);
    expect($app->decided_at)->not->toBeNull();
});

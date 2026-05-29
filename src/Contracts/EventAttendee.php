<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Contracts;

interface EventAttendee
{
    public function getKey(): int|string;

    public function getEventAttendeeDisplayName(): string;

    public function getEventAttendeeEmail(): string;
}

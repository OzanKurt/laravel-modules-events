<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Contracts;

interface EventOrganizer
{
    public function getKey(): int|string;

    public function getEventOrganizerDisplayName(): string;
}

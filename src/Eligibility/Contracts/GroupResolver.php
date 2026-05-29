<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Eligibility\Contracts;

use Illuminate\Database\Eloquent\Model;

interface GroupResolver
{
    /** @return array<int, string> Group identifiers the user belongs to. */
    public function groupsFor(Model $user): array;
}

<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Flow\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Kurt\Modules\Events\Flow\Models\WaitlistEntry;

final class WaitlistExpired
{
    use Dispatchable;

    public function __construct(public readonly WaitlistEntry $entry) {}
}

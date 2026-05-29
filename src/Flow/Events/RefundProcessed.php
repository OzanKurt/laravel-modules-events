<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Flow\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Kurt\Modules\Events\Flow\Models\Refund;

final class RefundProcessed
{
    use Dispatchable;

    public function __construct(public readonly Refund $refund) {}
}

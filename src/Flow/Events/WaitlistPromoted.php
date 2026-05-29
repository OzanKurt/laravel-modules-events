<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Flow\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Kurt\Modules\Events\Flow\Models\WaitlistEntry;

final class WaitlistPromoted implements ShouldBroadcast
{
    use Dispatchable;

    public function __construct(public readonly WaitlistEntry $entry) {}

    /** @return array<int, PrivateChannel> */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("events.waitlist.{$this->entry->ticket_type_id}.user.{$this->entry->user_id}"),
        ];
    }
}

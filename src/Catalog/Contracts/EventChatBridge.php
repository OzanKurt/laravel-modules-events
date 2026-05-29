<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Catalog\Contracts;

use Kurt\Modules\Events\Catalog\Models\Event;

interface EventChatBridge
{
    public function roomIdFor(Event $event): ?string;

    public function ensureRoomFor(Event $event): ?string;
}

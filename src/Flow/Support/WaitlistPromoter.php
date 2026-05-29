<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Flow\Support;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Support\Facades\DB;
use Kurt\Modules\Events\Flow\Enums\WaitlistStatus;
use Kurt\Modules\Events\Flow\Events\WaitlistPromoted;
use Kurt\Modules\Events\Flow\Models\WaitlistEntry;
use Kurt\Modules\Events\Ticketing\Models\TicketType;

final class WaitlistPromoter
{
    public function __construct(private readonly Repository $config) {}

    public function promoteNextFor(TicketType $type): ?WaitlistEntry
    {
        return DB::transaction(function () use ($type) {
            $next = WaitlistEntry::query()
                ->where('ticket_type_id', $type->id)
                ->where('status', WaitlistStatus::Waiting->value)
                ->orderBy('created_at')
                ->lockForUpdate()
                ->first();

            if ($next === null) {
                return null;
            }

            $next->forceFill([
                'status' => WaitlistStatus::Offered,
                'offered_at' => now(),
                'claim_expires_at' => now()->addSeconds((int) $this->config->get('events.waitlist.claim_window_seconds', 600)),
            ])->save();

            WaitlistPromoted::dispatch($next);

            return $next;
        });
    }
}

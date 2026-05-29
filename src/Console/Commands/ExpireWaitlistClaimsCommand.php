<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Console\Commands;

use Illuminate\Console\Command;
use Kurt\Modules\Events\Flow\Enums\WaitlistStatus;
use Kurt\Modules\Events\Flow\Events\WaitlistExpired;
use Kurt\Modules\Events\Flow\Models\WaitlistEntry;
use Kurt\Modules\Events\Flow\Support\WaitlistPromoter;
use Kurt\Modules\Events\Ticketing\Models\TicketType;

final class ExpireWaitlistClaimsCommand extends Command
{
    /** @var string */
    protected $signature = 'events:expire-waitlist-claims';

    /** @var string */
    protected $description = 'Expire offered waitlist entries past claim window and promote the next person.';

    public function handle(WaitlistPromoter $promoter): int
    {
        $expired = WaitlistEntry::query()
            ->where('status', WaitlistStatus::Offered->value)
            ->where('claim_expires_at', '<', now())
            ->get();

        $promoted = 0;
        $expiredCount = 0;
        foreach ($expired as $entry) {
            $entry->forceFill(['status' => WaitlistStatus::Expired])->save();
            WaitlistExpired::dispatch($entry);
            $expiredCount++;

            $type = TicketType::query()->find($entry->ticket_type_id);
            if ($type !== null && $promoter->promoteNextFor($type) !== null) {
                $promoted++;
            }
        }

        $this->info("Expired {$expiredCount} waitlist offer(s); promoted {$promoted} next claimant(s).");

        return self::SUCCESS;
    }
}

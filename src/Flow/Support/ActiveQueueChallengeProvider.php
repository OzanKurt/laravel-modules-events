<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Flow\Support;

use Illuminate\Database\Eloquent\Model;
use Kurt\Modules\Events\Flow\Contracts\QueueChallengeProvider;
use Kurt\Modules\Events\Flow\Enums\QueueStatus;
use Kurt\Modules\Events\Flow\Exceptions\QueueChallengeFailed;
use Kurt\Modules\Events\Flow\Models\SaleQueueEntry;

/**
 * Default sale-queue gate. When an event has a sale queue in use (at least one
 * entry exists), a buyer may only reserve while they hold a currently-Active,
 * non-expired queue entry. Events that do not use the queue are unaffected.
 */
final class ActiveQueueChallengeProvider implements QueueChallengeProvider
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function verify(Model $user, string $challengeToken, array $context = []): void
    {
        $eventId = $context['event_id'] ?? null;
        if ($eventId === null) {
            return;
        }

        $queueInUse = SaleQueueEntry::query()->where('event_id', $eventId)->exists();
        if (! $queueInUse) {
            return;
        }

        $admitted = SaleQueueEntry::query()
            ->where('event_id', $eventId)
            ->where('user_id', $user->getKey())
            ->where('status', QueueStatus::Active->value)
            ->where(function ($query): void {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->exists();

        if (! $admitted) {
            throw new QueueChallengeFailed('User is not admitted from the sale queue.');
        }
    }
}

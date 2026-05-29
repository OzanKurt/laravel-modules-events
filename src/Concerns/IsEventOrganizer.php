<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Kurt\Modules\Events\Catalog\Models\Event;
use Kurt\Modules\Events\Flow\Models\PayoutLedgerEntry;
use Kurt\Modules\Events\Ticketing\Models\Order;

/**
 * @mixin Model
 */
trait IsEventOrganizer
{
    /**
     * @return BelongsToMany<Event, $this>
     */
    public function eventsOrganized(): BelongsToMany
    {
        return $this->belongsToMany(Event::class, 'events_event_organizers')
            ->withPivot(['role', 'commission_basis_points'])
            ->withTimestamps();
    }

    /**
     * @return HasMany<Order, $this>
     */
    public function eventOrders(): HasMany
    {
        return $this->hasMany(Order::class, 'user_id', $this->getKeyName());
    }

    /**
     * @return HasMany<PayoutLedgerEntry, $this>
     */
    public function payoutLedger(): HasMany
    {
        return $this->hasMany(PayoutLedgerEntry::class, 'organizer_user_id', $this->getKeyName());
    }

    public function getEventOrganizerDisplayName(): string
    {
        return (string) ($this->getAttribute('name') ?? $this->getAttribute('email') ?? $this->getKey());
    }
}

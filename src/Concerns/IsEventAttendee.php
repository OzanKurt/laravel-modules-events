<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Kurt\Modules\Events\Attendance\Models\Application;
use Kurt\Modules\Events\Attendance\Models\Attendee;
use Kurt\Modules\Events\Ticketing\Models\Ticket;

/**
 * @mixin Model
 */
trait IsEventAttendee
{
    /**
     * @return HasMany<Ticket, $this>
     */
    public function eventTickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'holder_id', $this->getKeyName());
    }

    /**
     * @return HasMany<Application, $this>
     */
    public function eventApplications(): HasMany
    {
        return $this->hasMany(Application::class, 'applicant_id', $this->getKeyName());
    }

    /**
     * @return HasMany<Attendee, $this>
     */
    public function eventAttendances(): HasMany
    {
        return $this->hasMany(Attendee::class, 'user_id', $this->getKeyName());
    }

    public function getEventAttendeeDisplayName(): string
    {
        return (string) ($this->getAttribute('name') ?? $this->getAttribute('email') ?? $this->getKey());
    }

    public function getEventAttendeeEmail(): string
    {
        return (string) ($this->getAttribute('email') ?? '');
    }
}

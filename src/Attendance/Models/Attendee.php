<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Attendance\Models;

use Database\Factories\Kurt\Modules\Events\Attendance\AttendeeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Kurt\Modules\Core\Concerns\ResolvesUser;
use Kurt\Modules\Events\Attendance\Enums\AttendeeStatus;
use Kurt\Modules\Events\Catalog\Enums\AttendeeListVisibility;
use Kurt\Modules\Events\Catalog\Models\Event;
use Kurt\Modules\Events\Ticketing\Models\SessionCheckIn;
use Kurt\Modules\Events\Ticketing\Models\Ticket;

/**
 * @property int $id
 * @property int $event_id
 * @property int $ticket_id
 * @property int $user_id
 * @property AttendeeStatus $status
 * @property array<string, mixed> $profile
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Event $event
 */
class Attendee extends Model
{
    /** @use HasFactory<AttendeeFactory> */
    use HasFactory;

    use ResolvesUser;

    protected $table = 'events_attendees';

    /** @var list<string> */
    protected $fillable = ['event_id', 'ticket_id', 'user_id', 'status', 'profile'];

    /** @var array<string, string> */
    protected $casts = [
        'status' => AttendeeStatus::class,
        'profile' => 'array',
    ];

    /**
     * @return BelongsTo<Event, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * @return BelongsTo<Ticket, $this>
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    /**
     * @return BelongsTo<Model, $this>
     */
    public function user(): BelongsTo
    {
        return $this->userBelongsTo();
    }

    /**
     * @return HasMany<AttendanceResponse, $this>
     */
    public function responses(): HasMany
    {
        return $this->hasMany(AttendanceResponse::class);
    }

    /**
     * @return HasMany<SessionCheckIn, $this>
     */
    public function checkIns(): HasMany
    {
        return $this->hasMany(SessionCheckIn::class, 'ticket_id', 'ticket_id');
    }

    /**
     * Effective list visibility — most restrictive of event-level and self-level.
     */
    public function listVisibility(): AttendeeListVisibility
    {
        $eventLevel = $this->event->attendee_list_visibility;
        $profile = $this->profile;
        $selfRaw = $profile['list_visibility'] ?? 'public';
        $selfLevel = $selfRaw === 'private'
            ? AttendeeListVisibility::Private
            : AttendeeListVisibility::Public;

        return $eventLevel->isMoreRestrictiveThan($selfLevel) ? $eventLevel : $selfLevel;
    }

    protected static function newFactory(): AttendeeFactory
    {
        return AttendeeFactory::new();
    }
}

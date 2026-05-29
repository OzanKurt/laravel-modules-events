<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Catalog\Models;

use Database\Factories\Kurt\Modules\Events\Catalog\EventOrganizerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Kurt\Modules\Core\Concerns\ResolvesUser;
use Kurt\Modules\Events\Catalog\Enums\OrganizerRole;

/**
 * @property int $id
 * @property int $event_id
 * @property int $user_id
 * @property OrganizerRole $role
 * @property int|null $commission_basis_points
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class EventOrganizer extends Model
{
    /** @use HasFactory<EventOrganizerFactory> */
    use HasFactory;

    use ResolvesUser;

    protected $table = 'events_event_organizers';

    /** @var list<string> */
    protected $fillable = ['event_id', 'user_id', 'role', 'commission_basis_points'];

    /** @var array<string, string> */
    protected $casts = [
        'role' => OrganizerRole::class,
        'commission_basis_points' => 'integer',
    ];

    /**
     * @return BelongsTo<Event, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * @return BelongsTo<Model, $this>
     */
    public function user(): BelongsTo
    {
        return $this->userBelongsTo();
    }

    protected static function newFactory(): EventOrganizerFactory
    {
        return EventOrganizerFactory::new();
    }
}

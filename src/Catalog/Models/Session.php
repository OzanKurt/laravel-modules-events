<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Catalog\Models;

use Cviebrock\EloquentSluggable\Sluggable;
use Database\Factories\Kurt\Modules\Events\Catalog\SessionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Kurt\Modules\Events\Ticketing\Models\SessionCheckIn;
use Kurt\Modules\Events\Ticketing\Models\TicketType;
use Spatie\Translatable\HasTranslations;

/**
 * @property int $id
 * @property int $event_id
 * @property string $slug
 * @property string $title
 * @property string|null $description
 * @property Carbon $starts_at
 * @property Carbon $ends_at
 * @property int|null $capacity
 * @property string|null $location_name
 * @property int $position
 * @property int $attendees_count
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Session extends Model
{
    /** @use HasFactory<SessionFactory> */
    use HasFactory;

    use HasTranslations;
    use Sluggable;

    protected $table = 'events_sessions';

    /** @var list<string> */
    public array $translatable = ['title', 'description'];

    /** @var list<string> */
    protected $fillable = [
        'event_id', 'slug', 'title', 'description',
        'starts_at', 'ends_at',
        'capacity', 'location_name', 'position', 'attendees_count',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'capacity' => 'integer',
        'position' => 'integer',
        'attendees_count' => 'integer',
    ];

    /**
     * @return array<string, array<string, mixed>>
     */
    public function sluggable(): array
    {
        return ['slug' => ['source' => 'title', 'onUpdate' => true]];
    }

    /**
     * @return BelongsTo<Event, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * @return BelongsToMany<TicketType, $this>
     */
    public function ticketTypes(): BelongsToMany
    {
        return $this->belongsToMany(TicketType::class, 'events_ticket_type_session');
    }

    /**
     * @return HasMany<SessionCheckIn, $this>
     */
    public function checkIns(): HasMany
    {
        return $this->hasMany(SessionCheckIn::class);
    }

    protected static function newFactory(): SessionFactory
    {
        return SessionFactory::new();
    }
}

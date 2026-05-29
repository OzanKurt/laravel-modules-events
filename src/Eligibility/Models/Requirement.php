<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Eligibility\Models;

use Database\Factories\Kurt\Modules\Events\Eligibility\RequirementFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Kurt\Modules\Events\Catalog\Models\Event;
use Kurt\Modules\Events\Eligibility\Enums\RequirementType;
use Kurt\Modules\Events\Ticketing\Models\TicketType;

/**
 * @property int $id
 * @property int|null $event_id
 * @property int|null $ticket_type_id
 * @property RequirementType $type
 * @property array<string, mixed> $payload
 * @property bool $strict
 * @property int $position
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Requirement extends Model
{
    /** @use HasFactory<RequirementFactory> */
    use HasFactory;

    protected $table = 'events_requirements';

    /** @var list<string> */
    protected $fillable = [
        'event_id', 'ticket_type_id',
        'type', 'payload', 'strict', 'position',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'type' => RequirementType::class,
        'payload' => 'array',
        'strict' => 'boolean',
        'position' => 'integer',
    ];

    /**
     * @return BelongsTo<Event, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * @return BelongsTo<TicketType, $this>
     */
    public function ticketType(): BelongsTo
    {
        return $this->belongsTo(TicketType::class);
    }

    /**
     * @return HasMany<RequirementCheck, $this>
     */
    public function checks(): HasMany
    {
        return $this->hasMany(RequirementCheck::class);
    }

    protected static function newFactory(): RequirementFactory
    {
        return RequirementFactory::new();
    }
}

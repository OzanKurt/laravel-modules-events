<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Attendance\Models;

use Database\Factories\Kurt\Modules\Events\Attendance\ApplicationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Kurt\Modules\Core\Concerns\ResolvesUser;
use Kurt\Modules\Events\Attendance\Enums\ApplicationStatus;
use Kurt\Modules\Events\Catalog\Models\Event;
use Kurt\Modules\Events\Eligibility\Models\RequirementCheck;
use Kurt\Modules\Events\Ticketing\Models\Order;
use Kurt\Modules\Events\Ticketing\Models\TicketType;

/**
 * @property int $id
 * @property int $event_id
 * @property int $ticket_type_id
 * @property int $applicant_id
 * @property ApplicationStatus $status
 * @property Carbon $submitted_at
 * @property Carbon|null $decided_at
 * @property int|null $decided_by
 * @property string|null $decision_note
 * @property int|null $reservation_order_id
 * @property array<string, mixed>|null $metadata
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Application extends Model
{
    /** @use HasFactory<ApplicationFactory> */
    use HasFactory;

    use ResolvesUser;

    protected $table = 'events_applications';

    /** @var list<string> */
    protected $fillable = [
        'event_id', 'ticket_type_id', 'applicant_id',
        'status', 'submitted_at', 'decided_at', 'decided_by',
        'decision_note', 'reservation_order_id', 'metadata',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'status' => ApplicationStatus::class,
        'submitted_at' => 'datetime',
        'decided_at' => 'datetime',
        'metadata' => 'array',
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
     * @return BelongsTo<Model, $this>
     */
    public function applicant(): BelongsTo
    {
        return $this->userBelongsTo('applicant_id');
    }

    /**
     * @return BelongsTo<Order, $this>
     */
    public function reservationOrder(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'reservation_order_id');
    }

    /**
     * @return HasMany<RequirementCheck, $this>
     */
    public function requirementChecks(): HasMany
    {
        return $this->hasMany(RequirementCheck::class);
    }

    protected static function newFactory(): ApplicationFactory
    {
        return ApplicationFactory::new();
    }
}

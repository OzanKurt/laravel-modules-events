<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Ticketing\Models;

use Cviebrock\EloquentSluggable\Sluggable;
use Database\Factories\Kurt\Modules\Events\Ticketing\TicketTypeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Kurt\Modules\Events\Attendance\Models\AttendanceForm;
use Kurt\Modules\Events\Catalog\Models\Event;
use Kurt\Modules\Events\Catalog\Models\Session;
use Kurt\Modules\Events\Ticketing\Enums\TicketTypeMode;
use Spatie\Translatable\HasTranslations;

/**
 * @property int $id
 * @property int $event_id
 * @property string $slug
 * @property string $name
 * @property string|null $description
 * @property TicketTypeMode $mode
 * @property int $price_minor
 * @property string $currency
 * @property bool $refundable
 * @property int|null $self_cancel_deadline_hours_before_event
 * @property int|null $capacity
 * @property int $sold_count
 * @property Carbon|null $sale_starts_at
 * @property Carbon|null $sale_ends_at
 * @property int $max_per_order
 * @property int|null $minimum_price_minor
 * @property int|null $suggested_price_minor
 * @property int|null $attendance_form_id
 * @property bool $transferable
 * @property int|null $transfer_deadline_hours_before_event
 * @property int|null $transfer_fee_minor
 * @property string|null $transfer_fee_currency
 * @property bool $consumer_protection_exempt
 * @property array<string, mixed>|null $metadata
 * @property int $position
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
class TicketType extends Model
{
    /** @use HasFactory<TicketTypeFactory> */
    use HasFactory;

    use HasTranslations;
    use Sluggable;
    use SoftDeletes;

    protected $table = 'events_ticket_types';

    /** @var list<string> */
    public array $translatable = ['name', 'description'];

    /** @var list<string> */
    protected $fillable = [
        'event_id', 'slug', 'name', 'description', 'mode',
        'price_minor', 'currency', 'refundable',
        'self_cancel_deadline_hours_before_event',
        'capacity', 'sold_count',
        'sale_starts_at', 'sale_ends_at',
        'max_per_order', 'minimum_price_minor', 'suggested_price_minor',
        'attendance_form_id',
        'transferable', 'transfer_deadline_hours_before_event',
        'transfer_fee_minor', 'transfer_fee_currency',
        'consumer_protection_exempt',
        'metadata', 'position',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'mode' => TicketTypeMode::class,
        'refundable' => 'boolean',
        'transferable' => 'boolean',
        'consumer_protection_exempt' => 'boolean',
        'sale_starts_at' => 'datetime',
        'sale_ends_at' => 'datetime',
        'price_minor' => 'integer',
        'capacity' => 'integer',
        'sold_count' => 'integer',
        'max_per_order' => 'integer',
        'minimum_price_minor' => 'integer',
        'suggested_price_minor' => 'integer',
        'transfer_deadline_hours_before_event' => 'integer',
        'transfer_fee_minor' => 'integer',
        'self_cancel_deadline_hours_before_event' => 'integer',
        'metadata' => 'array',
        'position' => 'integer',
    ];

    /**
     * @return array<string, array<string, mixed>>
     */
    public function sluggable(): array
    {
        return ['slug' => ['source' => 'name', 'onUpdate' => true]];
    }

    /**
     * @return BelongsTo<Event, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * @return HasMany<PriceTier, $this>
     */
    public function priceTiers(): HasMany
    {
        return $this->hasMany(PriceTier::class);
    }

    /**
     * @return BelongsToMany<Session, $this>
     */
    public function sessions(): BelongsToMany
    {
        return $this->belongsToMany(Session::class, 'events_ticket_type_session');
    }

    /**
     * @return HasMany<Ticket, $this>
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    /**
     * @return BelongsTo<AttendanceForm, $this>
     */
    public function attendanceForm(): BelongsTo
    {
        return $this->belongsTo(AttendanceForm::class);
    }

    public function activePriceTier(?Carbon $at = null): ?PriceTier
    {
        $when = $at ?? now();

        return $this->priceTiers()->orderBy('position')->get()
            ->first(
                fn (PriceTier $t) => ($t->starts_at === null || $t->starts_at->lessThanOrEqualTo($when))
                    && ($t->ends_at === null || $t->ends_at->greaterThan($when))
            );
    }

    public function currentUnitPriceMinor(?Carbon $at = null): int
    {
        $tier = $this->activePriceTier($at);

        return $tier === null ? $this->price_minor : $tier->price_minor;
    }

    protected static function newFactory(): TicketTypeFactory
    {
        return TicketTypeFactory::new();
    }
}

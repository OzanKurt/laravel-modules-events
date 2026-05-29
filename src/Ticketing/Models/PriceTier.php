<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Ticketing\Models;

use Database\Factories\Kurt\Modules\Events\Ticketing\PriceTierFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $ticket_type_id
 * @property string $name
 * @property Carbon|null $starts_at
 * @property Carbon|null $ends_at
 * @property int $price_minor
 * @property int|null $capacity
 * @property int $sold_count
 * @property int $position
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class PriceTier extends Model
{
    /** @use HasFactory<PriceTierFactory> */
    use HasFactory;

    protected $table = 'events_price_tiers';

    /** @var list<string> */
    protected $fillable = [
        'ticket_type_id', 'name',
        'starts_at', 'ends_at',
        'price_minor', 'capacity', 'sold_count', 'position',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'price_minor' => 'integer',
        'capacity' => 'integer',
        'sold_count' => 'integer',
        'position' => 'integer',
    ];

    /**
     * @return BelongsTo<TicketType, $this>
     */
    public function ticketType(): BelongsTo
    {
        return $this->belongsTo(TicketType::class);
    }

    protected static function newFactory(): PriceTierFactory
    {
        return PriceTierFactory::new();
    }
}

<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Ticketing\Models;

use Database\Factories\Kurt\Modules\Events\Ticketing\OrderItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $order_id
 * @property int $ticket_type_id
 * @property int|null $price_tier_id
 * @property int $quantity
 * @property int $unit_price_minor
 * @property int $line_total_minor
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class OrderItem extends Model
{
    /** @use HasFactory<OrderItemFactory> */
    use HasFactory;

    protected $table = 'events_order_items';

    /** @var list<string> */
    protected $fillable = [
        'order_id', 'ticket_type_id', 'price_tier_id',
        'quantity', 'unit_price_minor', 'line_total_minor',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'quantity' => 'integer',
        'unit_price_minor' => 'integer',
        'line_total_minor' => 'integer',
    ];

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * @return BelongsTo<TicketType, $this>
     */
    public function ticketType(): BelongsTo
    {
        return $this->belongsTo(TicketType::class);
    }

    /**
     * @return BelongsTo<PriceTier, $this>
     */
    public function priceTier(): BelongsTo
    {
        return $this->belongsTo(PriceTier::class);
    }

    /**
     * @return HasMany<OrderItemAssignment, $this>
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(OrderItemAssignment::class);
    }

    /**
     * @return HasMany<Ticket, $this>
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    protected static function newFactory(): OrderItemFactory
    {
        return OrderItemFactory::new();
    }
}

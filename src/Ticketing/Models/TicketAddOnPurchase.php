<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Ticketing\Models;

use Database\Factories\Kurt\Modules\Events\Ticketing\TicketAddOnPurchaseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Kurt\Modules\Core\Concerns\ResolvesUser;
use Kurt\Modules\Events\Ticketing\Enums\AddOnPurchaseStatus;

/**
 * @property int $id
 * @property int $ticket_id
 * @property int $add_on_id
 * @property int $order_item_id
 * @property int $quantity
 * @property int $unit_price_minor
 * @property int $line_total_minor
 * @property AddOnPurchaseStatus $status
 * @property string|null $qr_token
 * @property Carbon|null $checked_in_at
 * @property int|null $checked_in_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
class TicketAddOnPurchase extends Model
{
    /** @use HasFactory<TicketAddOnPurchaseFactory> */
    use HasFactory;

    use ResolvesUser;
    use SoftDeletes;

    protected $table = 'events_ticket_add_on_purchases';

    /** @var list<string> */
    protected $fillable = [
        'ticket_id', 'add_on_id', 'order_item_id',
        'quantity', 'unit_price_minor', 'line_total_minor',
        'status', 'qr_token',
        'checked_in_at', 'checked_in_by',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'status' => AddOnPurchaseStatus::class,
        'checked_in_at' => 'datetime',
        'quantity' => 'integer',
        'unit_price_minor' => 'integer',
        'line_total_minor' => 'integer',
    ];

    /**
     * @return BelongsTo<Ticket, $this>
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    /**
     * @return BelongsTo<TicketAddOn, $this>
     */
    public function addOn(): BelongsTo
    {
        return $this->belongsTo(TicketAddOn::class, 'add_on_id');
    }

    /**
     * @return BelongsTo<OrderItem, $this>
     */
    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    /**
     * @return BelongsTo<Model, $this>
     */
    public function checkedInBy(): BelongsTo
    {
        return $this->userBelongsTo('checked_in_by');
    }

    protected static function newFactory(): TicketAddOnPurchaseFactory
    {
        return TicketAddOnPurchaseFactory::new();
    }
}

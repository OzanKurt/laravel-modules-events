<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Ticketing\Models;

use Database\Factories\Kurt\Modules\Events\Ticketing\TicketFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Kurt\Modules\Core\Concerns\ResolvesUser;
use Kurt\Modules\Events\Catalog\Models\Event;
use Kurt\Modules\Events\Ticketing\Enums\TicketStatus;

/**
 * @property int $id
 * @property int $order_item_id
 * @property int|null $order_item_assignment_id
 * @property int $ticket_type_id
 * @property int $event_id
 * @property int|null $holder_id
 * @property string $holder_name
 * @property string $holder_email
 * @property TicketStatus $status
 * @property string $qr_token
 * @property Carbon|null $checked_in_at
 * @property int|null $checked_in_by
 * @property int|null $transferred_from
 * @property Carbon|null $transferred_at
 * @property int|null $transfer_fee_order_id
 * @property array<string, mixed>|null $metadata
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Event $event
 * @property-read TicketType $ticketType
 */
class Ticket extends Model
{
    /** @use HasFactory<TicketFactory> */
    use HasFactory;

    use ResolvesUser;
    use SoftDeletes;

    protected $table = 'events_tickets';

    /** @var list<string> */
    protected $fillable = [
        'order_item_id', 'order_item_assignment_id',
        'ticket_type_id', 'event_id',
        'holder_id', 'holder_name', 'holder_email',
        'status', 'qr_token',
        'checked_in_at', 'checked_in_by',
        'transferred_from', 'transferred_at', 'transfer_fee_order_id',
        'metadata',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'status' => TicketStatus::class,
        'checked_in_at' => 'datetime',
        'transferred_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * @return BelongsTo<OrderItem, $this>
     */
    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    /**
     * @return BelongsTo<OrderItemAssignment, $this>
     */
    public function orderItemAssignment(): BelongsTo
    {
        return $this->belongsTo(OrderItemAssignment::class);
    }

    /**
     * @return BelongsTo<TicketType, $this>
     */
    public function ticketType(): BelongsTo
    {
        return $this->belongsTo(TicketType::class);
    }

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
    public function holder(): BelongsTo
    {
        return $this->userBelongsTo('holder_id');
    }

    /**
     * @return BelongsTo<Model, $this>
     */
    public function transferredFrom(): BelongsTo
    {
        return $this->userBelongsTo('transferred_from');
    }

    /**
     * @return HasMany<TicketAddOnPurchase, $this>
     */
    public function addOnPurchases(): HasMany
    {
        return $this->hasMany(TicketAddOnPurchase::class);
    }

    /**
     * @return BelongsTo<Order, $this>
     */
    public function transferFeeOrder(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'transfer_fee_order_id');
    }

    public function isCheckedIn(): bool
    {
        return $this->status === TicketStatus::CheckedIn;
    }

    public function transferable(): bool
    {
        $type = $this->ticketType;

        if (! $type->transferable) {
            return false;
        }

        if ($type->transfer_deadline_hours_before_event !== null) {
            $cutoff = $this->event->starts_at->copy()
                ->subHours($type->transfer_deadline_hours_before_event);

            if (now()->greaterThanOrEqualTo($cutoff)) {
                return false;
            }
        }

        return true;
    }

    protected static function newFactory(): TicketFactory
    {
        return TicketFactory::new();
    }
}

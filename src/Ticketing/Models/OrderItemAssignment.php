<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Ticketing\Models;

use Database\Factories\Kurt\Modules\Events\Ticketing\OrderItemAssignmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Kurt\Modules\Core\Concerns\ResolvesUser;

/**
 * @property int $id
 * @property int $order_item_id
 * @property int $seat_index
 * @property int|null $holder_user_id
 * @property string $holder_name
 * @property string $holder_email
 * @property array<string, mixed>|null $holder_metadata
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class OrderItemAssignment extends Model
{
    /** @use HasFactory<OrderItemAssignmentFactory> */
    use HasFactory;

    use ResolvesUser;

    protected $table = 'events_order_item_assignments';

    /** @var list<string> */
    protected $fillable = [
        'order_item_id', 'seat_index',
        'holder_user_id', 'holder_name', 'holder_email', 'holder_metadata',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'seat_index' => 'integer',
        'holder_metadata' => 'array',
    ];

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
    public function holder(): BelongsTo
    {
        return $this->userBelongsTo('holder_user_id');
    }

    protected static function newFactory(): OrderItemAssignmentFactory
    {
        return OrderItemAssignmentFactory::new();
    }
}

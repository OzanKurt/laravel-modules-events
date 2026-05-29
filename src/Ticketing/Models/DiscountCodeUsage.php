<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Ticketing\Models;

use Database\Factories\Kurt\Modules\Events\Ticketing\DiscountCodeUsageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Kurt\Modules\Core\Concerns\ResolvesUser;

/**
 * @property int $id
 * @property int $discount_code_id
 * @property int $order_id
 * @property int $user_id
 * @property int $applied_minor
 * @property string $currency
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class DiscountCodeUsage extends Model
{
    /** @use HasFactory<DiscountCodeUsageFactory> */
    use HasFactory;

    use ResolvesUser;

    protected $table = 'events_discount_code_usages';

    /** @var list<string> */
    protected $fillable = [
        'discount_code_id', 'order_id', 'user_id',
        'applied_minor', 'currency',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'applied_minor' => 'integer',
    ];

    /**
     * @return BelongsTo<DiscountCode, $this>
     */
    public function discountCode(): BelongsTo
    {
        return $this->belongsTo(DiscountCode::class);
    }

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * @return BelongsTo<Model, $this>
     */
    public function user(): BelongsTo
    {
        return $this->userBelongsTo();
    }

    protected static function newFactory(): DiscountCodeUsageFactory
    {
        return DiscountCodeUsageFactory::new();
    }
}

<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Ticketing\Models;

use Database\Factories\Kurt\Modules\Events\Ticketing\OrderFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Kurt\Modules\Core\Concerns\ResolvesUser;
use Kurt\Modules\Events\Catalog\Models\Event;
use Kurt\Modules\Events\Flow\Enums\RefundStatus;
use Kurt\Modules\Events\Flow\Models\PayoutLedgerEntry;
use Kurt\Modules\Events\Flow\Models\Refund;
use Kurt\Modules\Events\Ticketing\Enums\OrderStatus;

/**
 * @property int $id
 * @property int $event_id
 * @property int $user_id
 * @property OrderStatus $status
 * @property int $subtotal_minor
 * @property int $discount_minor
 * @property int $tax_minor
 * @property int $total_minor
 * @property int|null $tax_rate_basis_points
 * @property string $currency
 * @property int|null $discount_code_id
 * @property int|null $referral_link_id
 * @property string|null $processor
 * @property string|null $processor_reference
 * @property Carbon|null $paid_at
 * @property Carbon|null $assignment_completed_at
 * @property array<string, mixed>|null $metadata
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    use ResolvesUser;
    use SoftDeletes;

    protected $table = 'events_orders';

    /** @var list<string> */
    protected $fillable = [
        'event_id', 'user_id', 'status',
        'subtotal_minor', 'discount_minor', 'tax_minor', 'total_minor',
        'tax_rate_basis_points', 'currency',
        'discount_code_id', 'referral_link_id',
        'processor', 'processor_reference',
        'paid_at', 'assignment_completed_at',
        'metadata',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'status' => OrderStatus::class,
        'paid_at' => 'datetime',
        'assignment_completed_at' => 'datetime',
        'subtotal_minor' => 'integer',
        'discount_minor' => 'integer',
        'tax_minor' => 'integer',
        'total_minor' => 'integer',
        'tax_rate_basis_points' => 'integer',
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
     * @return BelongsTo<Model, $this>
     */
    public function buyer(): BelongsTo
    {
        return $this->userBelongsTo();
    }

    /**
     * @return HasMany<OrderItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * @return HasManyThrough<Ticket, OrderItem, $this>
     */
    public function tickets(): HasManyThrough
    {
        return $this->hasManyThrough(Ticket::class, OrderItem::class);
    }

    /**
     * @return BelongsTo<DiscountCode, $this>
     */
    public function discountCode(): BelongsTo
    {
        return $this->belongsTo(DiscountCode::class);
    }

    /**
     * @return BelongsTo<ReferralLink, $this>
     */
    public function referralLink(): BelongsTo
    {
        return $this->belongsTo(ReferralLink::class);
    }

    /**
     * @return HasMany<Refund, $this>
     */
    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class);
    }

    /**
     * @return HasMany<PayoutLedgerEntry, $this>
     */
    public function payoutEntries(): HasMany
    {
        return $this->hasMany(PayoutLedgerEntry::class);
    }

    public function recomputeTotalsAfterRefund(): void
    {
        $refundedMinor = (int) $this->refunds()
            ->where('status', RefundStatus::Processed->value)
            ->sum('amount_minor');

        if ($refundedMinor >= $this->total_minor) {
            $this->status = OrderStatus::Refunded;
        } elseif ($refundedMinor > 0) {
            $this->status = OrderStatus::PartiallyRefunded;
        }

        $this->save();
    }

    protected static function newFactory(): OrderFactory
    {
        return OrderFactory::new();
    }
}

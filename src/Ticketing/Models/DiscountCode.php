<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Ticketing\Models;

use Database\Factories\Kurt\Modules\Events\Ticketing\DiscountCodeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Kurt\Modules\Events\Catalog\Models\Event;
use Kurt\Modules\Events\Ticketing\Enums\DiscountApplicationScope;
use Kurt\Modules\Events\Ticketing\Enums\DiscountKind;
use Kurt\Modules\Events\Ticketing\Enums\DiscountScope;

/**
 * @property int $id
 * @property string $code
 * @property string|null $description
 * @property DiscountKind $kind
 * @property int $amount_minor
 * @property string|null $currency
 * @property DiscountApplicationScope $application_scope
 * @property DiscountScope $applies_to
 * @property Carbon|null $starts_at
 * @property Carbon|null $expires_at
 * @property int|null $max_uses_total
 * @property int|null $max_uses_per_user
 * @property int $uses_count
 * @property bool $active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
class DiscountCode extends Model
{
    /** @use HasFactory<DiscountCodeFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $table = 'events_discount_codes';

    /** @var list<string> */
    protected $fillable = [
        'code', 'description',
        'kind', 'amount_minor', 'currency',
        'application_scope', 'applies_to',
        'starts_at', 'expires_at',
        'max_uses_total', 'max_uses_per_user', 'uses_count',
        'active',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'kind' => DiscountKind::class,
        'applies_to' => DiscountScope::class,
        'application_scope' => DiscountApplicationScope::class,
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'active' => 'boolean',
        'amount_minor' => 'integer',
        'max_uses_total' => 'integer',
        'max_uses_per_user' => 'integer',
        'uses_count' => 'integer',
    ];

    /**
     * @return BelongsToMany<Event, $this>
     */
    public function events(): BelongsToMany
    {
        return $this->belongsToMany(Event::class, 'events_discount_code_event', 'discount_code_id', 'event_id');
    }

    /**
     * @return HasMany<DiscountCodeUsage, $this>
     */
    public function usages(): HasMany
    {
        return $this->hasMany(DiscountCodeUsage::class);
    }

    public function isActive(?Carbon $at = null): bool
    {
        $when = $at ?? now();

        if (! $this->active) {
            return false;
        }

        if ($this->starts_at !== null && $when->lessThan($this->starts_at)) {
            return false;
        }

        if ($this->expires_at !== null && $when->greaterThan($this->expires_at)) {
            return false;
        }

        if ($this->max_uses_total !== null && $this->uses_count >= $this->max_uses_total) {
            return false;
        }

        return true;
    }

    public function usedByUserCount(Model $user): int
    {
        return $this->usages()->where('user_id', $user->getKey())->count();
    }

    public function appliesToEvent(Event $event): bool
    {
        if ($this->applies_to === DiscountScope::Global) {
            return true;
        }

        return $this->events()->where('events_events.id', $event->getKey())->exists();
    }

    protected static function newFactory(): DiscountCodeFactory
    {
        return DiscountCodeFactory::new();
    }
}

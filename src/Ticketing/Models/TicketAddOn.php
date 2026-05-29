<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Ticketing\Models;

use Cviebrock\EloquentSluggable\Sluggable;
use Database\Factories\Kurt\Modules\Events\Ticketing\TicketAddOnFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Kurt\Modules\Events\Catalog\Models\Event;
use Spatie\Translatable\HasTranslations;

/**
 * @property int $id
 * @property int $event_id
 * @property string $slug
 * @property string $name
 * @property string|null $description
 * @property int $price_minor
 * @property string $currency
 * @property int|null $capacity
 * @property int $sold_count
 * @property bool $scannable
 * @property int $position
 * @property bool $active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
class TicketAddOn extends Model
{
    /** @use HasFactory<TicketAddOnFactory> */
    use HasFactory;

    use HasTranslations;
    use Sluggable;
    use SoftDeletes;

    protected $table = 'events_ticket_add_ons';

    /** @var list<string> */
    public array $translatable = ['name', 'description'];

    /** @var list<string> */
    protected $fillable = [
        'event_id', 'slug', 'name', 'description',
        'price_minor', 'currency', 'capacity', 'sold_count',
        'scannable', 'position', 'active',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'price_minor' => 'integer',
        'capacity' => 'integer',
        'sold_count' => 'integer',
        'scannable' => 'boolean',
        'position' => 'integer',
        'active' => 'boolean',
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
     * @return HasMany<TicketAddOnPurchase, $this>
     */
    public function purchases(): HasMany
    {
        return $this->hasMany(TicketAddOnPurchase::class, 'add_on_id');
    }

    protected static function newFactory(): TicketAddOnFactory
    {
        return TicketAddOnFactory::new();
    }
}

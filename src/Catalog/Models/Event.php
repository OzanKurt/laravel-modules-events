<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Catalog\Models;

use Cviebrock\EloquentSluggable\Sluggable;
use Database\Factories\Kurt\Modules\Events\Catalog\EventFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Kurt\Modules\Events\Attendance\Models\Attendee;
use Kurt\Modules\Events\Catalog\Enums\AttendeeListVisibility;
use Kurt\Modules\Events\Catalog\Enums\EventStatus;
use Kurt\Modules\Events\Catalog\Enums\EventVisibility;
use Kurt\Modules\Events\Ticketing\Models\Order;
use Kurt\Modules\Events\Ticketing\Models\Ticket;
use Kurt\Modules\Events\Ticketing\Models\TicketType;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Translatable\HasTranslations;

/**
 * @property int $id
 * @property string $slug
 * @property string $title
 * @property string|null $description
 * @property int|null $category_id
 * @property EventStatus $status
 * @property EventVisibility $visibility
 * @property Carbon $starts_at
 * @property Carbon $ends_at
 * @property string $timezone
 * @property string|null $location_name
 * @property string|null $location_address
 * @property string|null $latitude
 * @property string|null $longitude
 * @property string|null $cover_path
 * @property array<int, mixed>|null $reminder_intervals
 * @property AttendeeListVisibility $attendee_list_visibility
 * @property int|null $parent_event_id
 * @property array<string, mixed>|null $recurrence_rule
 * @property int|null $capacity
 * @property Carbon|null $sale_starts_at
 * @property Carbon|null $sale_ends_at
 * @property Carbon|null $cancelled_at
 * @property int|null $cancelled_by
 * @property string|null $cancellation_reason
 * @property int $tickets_sold_count
 * @property int $attendees_count
 * @property int $applications_pending_count
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
class Event extends Model implements HasMedia
{
    /** @use HasFactory<EventFactory> */
    use HasFactory;

    use HasTranslations;
    use InteractsWithMedia;
    use Sluggable;
    use SoftDeletes;

    protected $table = 'events_events';

    /** @var list<string> */
    public array $translatable = ['title', 'description'];

    /** @var list<string> */
    protected $fillable = [
        'slug', 'title', 'description', 'category_id',
        'status', 'visibility',
        'starts_at', 'ends_at', 'timezone',
        'location_name', 'location_address', 'latitude', 'longitude',
        'cover_path', 'reminder_intervals', 'attendee_list_visibility',
        'parent_event_id', 'recurrence_rule',
        'capacity', 'sale_starts_at', 'sale_ends_at',
        'cancelled_at', 'cancelled_by', 'cancellation_reason',
        'tickets_sold_count', 'attendees_count', 'applications_pending_count',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'status' => EventStatus::class,
        'visibility' => EventVisibility::class,
        'attendee_list_visibility' => AttendeeListVisibility::class,
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'sale_starts_at' => 'datetime',
        'sale_ends_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'recurrence_rule' => 'array',
        'reminder_intervals' => 'array',
        'capacity' => 'integer',
        'tickets_sold_count' => 'integer',
        'attendees_count' => 'integer',
        'applications_pending_count' => 'integer',
    ];

    /**
     * @return array<string, array<string, mixed>>
     */
    public function sluggable(): array
    {
        return ['slug' => ['source' => 'title', 'onUpdate' => true]];
    }

    /**
     * @return BelongsTo<EventCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(EventCategory::class);
    }

    /**
     * @return BelongsToMany<EventTag, $this>
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(EventTag::class, 'events_event_tag', 'event_id', 'tag_id');
    }

    /**
     * @return HasMany<EventOrganizer, $this>
     */
    public function organizers(): HasMany
    {
        return $this->hasMany(EventOrganizer::class);
    }

    /**
     * @return HasMany<Session, $this>
     */
    public function sessions(): HasMany
    {
        return $this->hasMany(Session::class);
    }

    /**
     * @return HasMany<TicketType, $this>
     */
    public function ticketTypes(): HasMany
    {
        return $this->hasMany(TicketType::class);
    }

    /**
     * @return HasMany<Order, $this>
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * @return HasManyThrough<Attendee, Ticket, $this>
     */
    public function attendees(): HasManyThrough
    {
        return $this->hasManyThrough(Attendee::class, Ticket::class);
    }

    /**
     * @return BelongsTo<self, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_event_id');
    }

    /**
     * @return HasMany<self, $this>
     */
    public function occurrences(): HasMany
    {
        return $this->hasMany(self::class, 'parent_event_id');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('cover')->singleFile();
        $this->addMediaCollection('gallery');
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $thumb = $this->addMediaConversion('thumb');
        $thumb->width(320);
        $thumb->height(320);
        $thumb->nonQueued();

        $cover = $this->addMediaConversion('cover');
        $cover->width(1200);
        $cover->height(630);
        $cover->nonQueued();
    }

    /**
     * @param  Builder<self>  $q
     * @return Builder<self>
     */
    public function scopePublished(Builder $q): Builder
    {
        return $q->where('status', EventStatus::Published->value)->whereNull('cancelled_at');
    }

    /**
     * @param  Builder<self>  $q
     * @return Builder<self>
     */
    public function scopeUpcoming(Builder $q): Builder
    {
        return $q->where('starts_at', '>=', now());
    }

    /**
     * @param  Builder<self>  $q
     * @return Builder<self>
     */
    public function scopePast(Builder $q): Builder
    {
        return $q->where('starts_at', '<', now());
    }

    /**
     * @param  Builder<self>  $q
     * @return Builder<self>
     */
    public function scopeInCategory(Builder $q, EventCategory|int $category): Builder
    {
        return $q->where('category_id', $category instanceof EventCategory ? $category->getKey() : $category);
    }

    /**
     * @param  Builder<self>  $q
     * @param  array<int, int>|int  $tagIds
     * @return Builder<self>
     */
    public function scopeWithTags(Builder $q, array|int $tagIds, bool $matchAll = false): Builder
    {
        $ids = is_array($tagIds) ? $tagIds : [$tagIds];

        return $matchAll
            ? $q->whereHas('tags', fn ($t) => $t->whereIn('events_tags.id', $ids), '=', count($ids))
            : $q->whereHas('tags', fn ($t) => $t->whereIn('events_tags.id', $ids));
    }

    /**
     * @param  Builder<self>  $q
     * @return Builder<self>
     */
    public function scopeOrganizedBy(Builder $q, Model $user): Builder
    {
        return $q->whereHas('organizers', fn ($o) => $o->where('user_id', $user->getKey()));
    }

    /**
     * @param  Builder<self>  $q
     * @return Builder<self>
     */
    public function scopeNearLocation(Builder $q, float $lat, float $lng, float $radius): Builder
    {
        if (! config('events.search.geo.enabled')) {
            return $q->whereRaw('1=0');
        }

        $unit = config('events.search.geo.distance_unit') === 'mi' ? 3959 : 6371;

        return $q->whereNotNull('latitude')->whereNotNull('longitude')
            ->selectRaw(
                "*, ({$unit} * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) AS distance",
                [$lat, $lng, $lat]
            )
            ->having('distance', '<=', $radius);
    }

    /**
     * Bridge to the chat module, when wired through events.chat_bridge.provider.
     *
     * The bridge class must expose a `roomIdFor(Event $event): ?string` method.
     */
    public function chatRoomId(): ?string
    {
        $bridge = config('events.chat_bridge.provider');

        if ($bridge === null || ! is_string($bridge)) {
            return null;
        }

        $instance = app($bridge);

        if (! is_object($instance) || ! method_exists($instance, 'roomIdFor')) {
            return null;
        }

        $result = $instance->roomIdFor($this);

        return is_string($result) ? $result : null;
    }

    protected static function newFactory(): EventFactory
    {
        return EventFactory::new();
    }
}

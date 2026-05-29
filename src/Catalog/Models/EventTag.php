<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Catalog\Models;

use Cviebrock\EloquentSluggable\Sluggable;
use Database\Factories\Kurt\Modules\Events\Catalog\EventTagFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;
use Spatie\Translatable\HasTranslations;

/**
 * @property int $id
 * @property string $slug
 * @property string $name
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class EventTag extends Model
{
    /** @use HasFactory<EventTagFactory> */
    use HasFactory;

    use HasTranslations;
    use Sluggable;

    protected $table = 'events_tags';

    /** @var list<string> */
    public array $translatable = ['name'];

    /** @var list<string> */
    protected $fillable = ['slug', 'name'];

    /**
     * @return array<string, array<string, mixed>>
     */
    public function sluggable(): array
    {
        return ['slug' => ['source' => 'name', 'onUpdate' => true]];
    }

    /**
     * @return BelongsToMany<Event, $this>
     */
    public function events(): BelongsToMany
    {
        return $this->belongsToMany(Event::class, 'events_event_tag', 'tag_id', 'event_id');
    }

    protected static function newFactory(): EventTagFactory
    {
        return EventTagFactory::new();
    }
}

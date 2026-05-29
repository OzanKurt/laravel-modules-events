<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Catalog\Models;

use Cviebrock\EloquentSluggable\Sluggable;
use Database\Factories\Kurt\Modules\Events\Catalog\EventTemplateFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Kurt\Modules\Core\Concerns\ResolvesUser;

/**
 * @property int $id
 * @property int $owner_id
 * @property string $slug
 * @property string $name
 * @property string|null $description
 * @property array<string, mixed> $payload
 * @property bool $is_public
 * @property int $used_count
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
class EventTemplate extends Model
{
    /** @use HasFactory<EventTemplateFactory> */
    use HasFactory;

    use ResolvesUser;
    use Sluggable;
    use SoftDeletes;

    protected $table = 'events_event_templates';

    /** @var list<string> */
    protected $fillable = ['owner_id', 'slug', 'name', 'description', 'payload', 'is_public', 'used_count'];

    /** @var array<string, string> */
    protected $casts = [
        'payload' => 'array',
        'is_public' => 'boolean',
        'used_count' => 'integer',
    ];

    /**
     * @return array<string, array<string, mixed>>
     */
    public function sluggable(): array
    {
        return ['slug' => ['source' => 'name']];
    }

    /**
     * @return BelongsTo<Model, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->userBelongsTo('owner_id');
    }

    protected static function newFactory(): EventTemplateFactory
    {
        return EventTemplateFactory::new();
    }
}

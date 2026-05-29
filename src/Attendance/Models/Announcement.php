<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Attendance\Models;

use Database\Factories\Kurt\Modules\Events\Attendance\AnnouncementFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Kurt\Modules\Core\Concerns\ResolvesUser;
use Kurt\Modules\Events\Attendance\Enums\AnnouncementAudience;
use Kurt\Modules\Events\Catalog\Models\Event;

/**
 * @property int $id
 * @property int $event_id
 * @property int $author_id
 * @property string $subject
 * @property string $body
 * @property AnnouncementAudience $audience
 * @property array<string, mixed>|null $audience_filter
 * @property Carbon|null $scheduled_for
 * @property Carbon|null $sent_at
 * @property int $recipient_count
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
class Announcement extends Model
{
    /** @use HasFactory<AnnouncementFactory> */
    use HasFactory;

    use ResolvesUser;
    use SoftDeletes;

    protected $table = 'events_announcements';

    /** @var list<string> */
    protected $fillable = [
        'event_id', 'author_id',
        'subject', 'body',
        'audience', 'audience_filter',
        'scheduled_for', 'sent_at', 'recipient_count',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'audience' => AnnouncementAudience::class,
        'audience_filter' => 'array',
        'scheduled_for' => 'datetime',
        'sent_at' => 'datetime',
        'recipient_count' => 'integer',
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
    public function author(): BelongsTo
    {
        return $this->userBelongsTo('author_id');
    }

    /**
     * @return HasMany<AnnouncementRecipient, $this>
     */
    public function recipients(): HasMany
    {
        return $this->hasMany(AnnouncementRecipient::class);
    }

    /**
     * @return HasManyThrough<Attendee, AnnouncementRecipient, $this>
     */
    public function attendees(): HasManyThrough
    {
        return $this->hasManyThrough(
            Attendee::class,
            AnnouncementRecipient::class,
            'announcement_id',
            'id',
            'id',
            'attendee_id'
        );
    }

    protected static function newFactory(): AnnouncementFactory
    {
        return AnnouncementFactory::new();
    }
}

<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Attendance\Models;

use Database\Factories\Kurt\Modules\Events\Attendance\AnnouncementRecipientFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Kurt\Modules\Events\Attendance\Enums\AnnouncementRecipientStatus;

/**
 * @property int $id
 * @property int $announcement_id
 * @property int $attendee_id
 * @property AnnouncementRecipientStatus $status
 * @property string|null $notification_id
 * @property Carbon|null $sent_at
 * @property Carbon|null $opened_at
 * @property string|null $failure_reason
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class AnnouncementRecipient extends Model
{
    /** @use HasFactory<AnnouncementRecipientFactory> */
    use HasFactory;

    protected $table = 'events_announcement_recipients';

    /** @var list<string> */
    protected $fillable = [
        'announcement_id', 'attendee_id',
        'status', 'notification_id',
        'sent_at', 'opened_at', 'failure_reason',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'status' => AnnouncementRecipientStatus::class,
        'sent_at' => 'datetime',
        'opened_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<Announcement, $this>
     */
    public function announcement(): BelongsTo
    {
        return $this->belongsTo(Announcement::class);
    }

    /**
     * @return BelongsTo<Attendee, $this>
     */
    public function attendee(): BelongsTo
    {
        return $this->belongsTo(Attendee::class);
    }

    protected static function newFactory(): AnnouncementRecipientFactory
    {
        return AnnouncementRecipientFactory::new();
    }
}

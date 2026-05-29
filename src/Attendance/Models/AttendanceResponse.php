<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Attendance\Models;

use Database\Factories\Kurt\Modules\Events\Attendance\AttendanceResponseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $attendee_id
 * @property int $attendance_form_id
 * @property array<string, mixed> $answers
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class AttendanceResponse extends Model
{
    /** @use HasFactory<AttendanceResponseFactory> */
    use HasFactory;

    protected $table = 'events_attendance_responses';

    /** @var list<string> */
    protected $fillable = ['attendee_id', 'attendance_form_id', 'answers'];

    /** @var array<string, string> */
    protected $casts = [
        'answers' => 'array',
    ];

    /**
     * @return BelongsTo<Attendee, $this>
     */
    public function attendee(): BelongsTo
    {
        return $this->belongsTo(Attendee::class);
    }

    /**
     * @return BelongsTo<AttendanceForm, $this>
     */
    public function attendanceForm(): BelongsTo
    {
        return $this->belongsTo(AttendanceForm::class);
    }

    protected static function newFactory(): AttendanceResponseFactory
    {
        return AttendanceResponseFactory::new();
    }
}

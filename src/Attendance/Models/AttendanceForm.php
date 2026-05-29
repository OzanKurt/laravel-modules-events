<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Attendance\Models;

use Database\Factories\Kurt\Modules\Events\Attendance\AttendanceFormFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Kurt\Modules\Events\Catalog\Models\Event;

/**
 * @property int $id
 * @property int $event_id
 * @property string $name
 * @property array<int, array<string, mixed>> $schema
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class AttendanceForm extends Model
{
    /** @use HasFactory<AttendanceFormFactory> */
    use HasFactory;

    protected $table = 'events_attendance_forms';

    /** @var list<string> */
    protected $fillable = ['event_id', 'name', 'schema'];

    /** @var array<string, string> */
    protected $casts = [
        'schema' => 'array',
    ];

    /**
     * @return BelongsTo<Event, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * @return HasMany<AttendanceResponse, $this>
     */
    public function responses(): HasMany
    {
        return $this->hasMany(AttendanceResponse::class);
    }

    /**
     * Validate an answer payload against the form schema.
     *
     * @param  array<string, mixed>  $answers
     * @return array<string, string> Field key => error message
     */
    public function validate(array $answers): array
    {
        $errors = [];

        foreach ($this->schema as $field) {
            $key = $field['key'] ?? null;
            $required = (bool) ($field['required'] ?? false);

            if (! is_string($key) || $key === '') {
                continue;
            }

            $value = $answers[$key] ?? null;

            if ($required && ($value === null || $value === '')) {
                $errors[$key] = sprintf('Field "%s" is required.', $field['label'] ?? $key);
            }
        }

        return $errors;
    }

    protected static function newFactory(): AttendanceFormFactory
    {
        return AttendanceFormFactory::new();
    }
}

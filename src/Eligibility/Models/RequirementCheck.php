<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Eligibility\Models;

use Database\Factories\Kurt\Modules\Events\Eligibility\RequirementCheckFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Kurt\Modules\Core\Concerns\ResolvesUser;
use Kurt\Modules\Events\Attendance\Models\Application;
use Kurt\Modules\Events\Attendance\Models\Attendee;
use Kurt\Modules\Events\Eligibility\Enums\CheckStatus;

/**
 * @property int $id
 * @property int|null $attendee_id
 * @property int|null $application_id
 * @property int $requirement_id
 * @property CheckStatus $status
 * @property array<string, mixed>|null $result
 * @property int|null $reviewed_by
 * @property Carbon|null $reviewed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class RequirementCheck extends Model
{
    /** @use HasFactory<RequirementCheckFactory> */
    use HasFactory;

    use ResolvesUser;

    protected $table = 'events_requirement_checks';

    /** @var list<string> */
    protected $fillable = [
        'attendee_id', 'application_id', 'requirement_id',
        'status', 'result',
        'reviewed_by', 'reviewed_at',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'status' => CheckStatus::class,
        'result' => 'array',
        'reviewed_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<Attendee, $this>
     */
    public function attendee(): BelongsTo
    {
        return $this->belongsTo(Attendee::class);
    }

    /**
     * @return BelongsTo<Application, $this>
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    /**
     * @return BelongsTo<Requirement, $this>
     */
    public function requirement(): BelongsTo
    {
        return $this->belongsTo(Requirement::class);
    }

    /**
     * @return BelongsTo<Model, $this>
     */
    public function reviewer(): BelongsTo
    {
        return $this->userBelongsTo('reviewed_by');
    }

    protected static function newFactory(): RequirementCheckFactory
    {
        return RequirementCheckFactory::new();
    }
}

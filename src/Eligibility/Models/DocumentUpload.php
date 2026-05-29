<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Eligibility\Models;

use Database\Factories\Kurt\Modules\Events\Eligibility\DocumentUploadFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Kurt\Modules\Events\Attendance\Models\Attendee;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * @property int $id
 * @property int $attendee_id
 * @property int $requirement_id
 * @property string|null $kind
 * @property string $filename
 * @property string $mime_type
 * @property int $byte_size
 * @property array<string, mixed>|null $metadata
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
class DocumentUpload extends Model implements HasMedia
{
    /** @use HasFactory<DocumentUploadFactory> */
    use HasFactory;

    use InteractsWithMedia;
    use SoftDeletes;

    protected $table = 'events_document_uploads';

    /** @var list<string> */
    protected $fillable = [
        'attendee_id', 'requirement_id',
        'kind', 'filename', 'mime_type', 'byte_size',
        'metadata',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'byte_size' => 'integer',
        'metadata' => 'array',
    ];

    /**
     * @return BelongsTo<Attendee, $this>
     */
    public function attendee(): BelongsTo
    {
        return $this->belongsTo(Attendee::class);
    }

    /**
     * @return BelongsTo<Requirement, $this>
     */
    public function requirement(): BelongsTo
    {
        return $this->belongsTo(Requirement::class);
    }

    /**
     * @return HasMany<DocumentVerification, $this>
     */
    public function verifications(): HasMany
    {
        return $this->hasMany(DocumentVerification::class);
    }

    public function registerMediaCollections(): void
    {
        $disk = config('events.documents.disk', 'private');
        $diskName = is_string($disk) ? $disk : 'private';
        $this->addMediaCollection('document')->useDisk($diskName)->singleFile();
    }

    protected static function newFactory(): DocumentUploadFactory
    {
        return DocumentUploadFactory::new();
    }
}

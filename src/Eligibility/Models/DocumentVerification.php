<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Eligibility\Models;

use Database\Factories\Kurt\Modules\Events\Eligibility\DocumentVerificationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Kurt\Modules\Core\Concerns\ResolvesUser;
use Kurt\Modules\Events\Eligibility\Enums\VerificationStatus;

/**
 * @property int $id
 * @property int $document_upload_id
 * @property VerificationStatus $status
 * @property int|null $decided_by
 * @property Carbon|null $decided_at
 * @property string|null $note
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class DocumentVerification extends Model
{
    /** @use HasFactory<DocumentVerificationFactory> */
    use HasFactory;

    use ResolvesUser;

    protected $table = 'events_document_verifications';

    /** @var list<string> */
    protected $fillable = [
        'document_upload_id',
        'status', 'decided_by', 'decided_at', 'note',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'status' => VerificationStatus::class,
        'decided_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<DocumentUpload, $this>
     */
    public function documentUpload(): BelongsTo
    {
        return $this->belongsTo(DocumentUpload::class);
    }

    /**
     * @return BelongsTo<Model, $this>
     */
    public function reviewer(): BelongsTo
    {
        return $this->userBelongsTo('decided_by');
    }

    protected static function newFactory(): DocumentVerificationFactory
    {
        return DocumentVerificationFactory::new();
    }
}

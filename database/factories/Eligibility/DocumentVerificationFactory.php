<?php

declare(strict_types=1);

namespace Database\Factories\Kurt\Modules\Events\Eligibility;

use Illuminate\Database\Eloquent\Factories\Factory;
use Kurt\Modules\Events\Eligibility\Enums\VerificationStatus;
use Kurt\Modules\Events\Eligibility\Models\DocumentUpload;
use Kurt\Modules\Events\Eligibility\Models\DocumentVerification;

/**
 * @extends Factory<DocumentVerification>
 */
class DocumentVerificationFactory extends Factory
{
    /** @var class-string<DocumentVerification> */
    protected $model = DocumentVerification::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'document_upload_id' => DocumentUpload::factory(),
            'status' => VerificationStatus::Pending,
        ];
    }
}

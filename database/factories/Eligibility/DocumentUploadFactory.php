<?php

declare(strict_types=1);

namespace Database\Factories\Kurt\Modules\Events\Eligibility;

use Illuminate\Database\Eloquent\Factories\Factory;
use Kurt\Modules\Events\Attendance\Models\Attendee;
use Kurt\Modules\Events\Eligibility\Models\DocumentUpload;
use Kurt\Modules\Events\Eligibility\Models\Requirement;

/**
 * @extends Factory<DocumentUpload>
 */
class DocumentUploadFactory extends Factory
{
    /** @var class-string<DocumentUpload> */
    protected $model = DocumentUpload::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'attendee_id' => Attendee::factory(),
            'requirement_id' => Requirement::factory(),
            'kind' => 'id_card',
            'filename' => 'id.pdf',
            'mime_type' => 'application/pdf',
            'byte_size' => 1024,
        ];
    }
}

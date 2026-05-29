<?php

declare(strict_types=1);

use Kurt\Modules\Events\Eligibility\Enums\VerificationStatus;
use Kurt\Modules\Events\Eligibility\Models\DocumentUpload;
use Kurt\Modules\Events\Eligibility\Models\DocumentVerification;

it('creates document with metadata', function () {
    $doc = DocumentUpload::factory()->create([
        'kind' => 'passport',
        'filename' => 'doc.pdf',
        'byte_size' => 2048,
        'metadata' => ['extracted_name' => 'A. Lice'],
    ]);

    expect($doc->kind)->toBe('passport');
    expect($doc->byte_size)->toBe(2048);
    expect($doc->metadata)->toBe(['extracted_name' => 'A. Lice']);
});

it('has many verifications', function () {
    $doc = DocumentUpload::factory()->create();
    DocumentVerification::factory()->create([
        'document_upload_id' => $doc->id,
        'status' => VerificationStatus::Verified,
    ]);

    expect($doc->verifications()->count())->toBe(1);
});

it('registers media collection', function () {
    $doc = DocumentUpload::factory()->create();
    $collections = $doc->getRegisteredMediaCollections();

    expect($collections)->toHaveCount(1);
    expect($collections->first()?->name)->toBe('document');
});

<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Eligibility\Contracts;

use Kurt\Modules\Events\Eligibility\Models\DocumentUpload;
use Kurt\Modules\Events\Eligibility\Models\DocumentVerification;

interface DocumentVerifier
{
    public function verify(DocumentUpload $upload): DocumentVerification;
}

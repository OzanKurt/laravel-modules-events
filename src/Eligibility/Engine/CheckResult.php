<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Eligibility\Engine;

use Kurt\Modules\Events\Eligibility\Enums\CheckStatus;

final readonly class CheckResult
{
    /** @param  array<string, mixed>  $data */
    public function __construct(
        public CheckStatus $status,
        public ?string $message = null,
        public array $data = [],
    ) {}

    /** @param  array<string, mixed>  $data */
    public static function pass(array $data = []): self
    {
        return new self(CheckStatus::Passed, null, $data);
    }

    /** @param  array<string, mixed>  $data */
    public static function fail(string $message, array $data = []): self
    {
        return new self(CheckStatus::Failed, $message, $data);
    }

    public static function pending(string $message = 'Awaiting review'): self
    {
        return new self(CheckStatus::Pending, $message);
    }
}

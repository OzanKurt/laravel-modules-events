<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Flow\Contracts;

use Illuminate\Database\Eloquent\Model;
use Kurt\Modules\Events\Flow\Exceptions\QueueChallengeFailed;

interface QueueChallengeProvider
{
    /**
     * @param  array<string, mixed>  $context
     *
     * @throws QueueChallengeFailed when the supplied token is invalid.
     */
    public function verify(Model $user, string $challengeToken, array $context = []): void;
}

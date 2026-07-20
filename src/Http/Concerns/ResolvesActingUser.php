<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Http\Concerns;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * Resolves the authenticated request user as an Eloquent {@see Model}, which is
 * what the module's domain services expect (they call `->getKey()` on the
 * organizer/buyer/scanner). Write routes carry the auth middleware, so a
 * non-model here means the app's user provider is misconfigured rather than a
 * genuine guest, and a 401 is the honest response.
 */
trait ResolvesActingUser
{
    protected function actingUser(Request $request): Model
    {
        $user = $request->user();

        if (! $user instanceof Model) {
            throw new AuthenticationException;
        }

        return $user;
    }
}

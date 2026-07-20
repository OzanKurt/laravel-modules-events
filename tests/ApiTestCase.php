<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Tests;

use Illuminate\Foundation\Application;
use Kurt\Modules\Events\Tests\Stubs\StubUser;

/**
 * Test case for the HTTP API surface. The REST routes are registered by the
 * provider at boot only when `events.http.mode` is `api`/`ui`, so the mode must
 * be set during environment definition (before providers boot) rather than in a
 * per-test beforeEach.
 */
abstract class ApiTestCase extends TestCase
{
    /**
     * @param  Application  $app
     */
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('events.http.mode', 'api');
        $app['config']->set('kurtmodules.user_model', StubUser::class);
    }
}

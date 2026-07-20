<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

/*
 * This file lives directly under Feature/ (not Feature/Http), so it uses the
 * base TestCase where events.http.mode defaults to `headless`. Safe-by-default:
 * with no opt-in, nothing is registered.
 */

it('registers no API routes in headless mode', function () {
    Route::getRoutes()->refreshNameLookups();

    expect(Route::has('events.api.events.index'))->toBeFalse()
        ->and(Route::has('events.api.registrations.register'))->toBeFalse();
});

it('does not resolve the events API endpoints in headless mode (404)', function () {
    $this->getJson('/api/events')->assertNotFound();
});

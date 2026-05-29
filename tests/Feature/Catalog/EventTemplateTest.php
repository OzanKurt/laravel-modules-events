<?php

declare(strict_types=1);

use Kurt\Modules\Events\Catalog\Models\EventTemplate;
use Kurt\Modules\Events\Tests\Stubs\StubUser;

it('creates a template with payload', function () {
    $user = StubUser::create(['email' => 'owner@test.dev']);
    $template = EventTemplate::factory()->create([
        'owner_id' => $user->id,
        'payload' => ['title' => 'Annual Meet', 'sessions' => [['title' => 'Welcome']]],
    ]);

    expect($template->payload)->toBeArray();
    expect($template->payload['title'])->toBe('Annual Meet');
    expect($template->owner->id)->toBe($user->id);
});

it('defaults is_public to false; public state flips it', function () {
    $publicTemplate = EventTemplate::factory()->public()->create();
    $privateTemplate = EventTemplate::factory()->create();

    expect($publicTemplate->is_public)->toBeTrue();
    expect($privateTemplate->is_public)->toBeFalse();
});

it('soft deletes', function () {
    $template = EventTemplate::factory()->create();
    $template->delete();

    expect(EventTemplate::count())->toBe(0);
    expect(EventTemplate::withTrashed()->count())->toBe(1);
});

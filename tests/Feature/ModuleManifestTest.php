<?php

declare(strict_types=1);

use Kurt\Modules\Core\Contracts\ModuleRegistry;

it('declares its manifest into the registry', function () {
    $registry = app(ModuleRegistry::class);

    expect($registry->has('events'))->toBeTrue()
        ->and($registry->get('events')->getName())->toBe('Events');
});

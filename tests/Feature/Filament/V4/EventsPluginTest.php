<?php

declare(strict_types=1);

use Filament\Facades\Filament;
use Kurt\Modules\Core\Support\FilamentVersion;
use Kurt\Modules\Events\Filament\EventsPlugin;
use Kurt\Modules\Events\Filament\V4\Resources\ApplicationResource;
use Kurt\Modules\Events\Filament\V4\Resources\DiscountCodeResource;
use Kurt\Modules\Events\Filament\V4\Resources\DocumentVerificationResource;
use Kurt\Modules\Events\Filament\V4\Resources\EventResource;
use Kurt\Modules\Events\Filament\V4\Resources\OrderResource;
use Kurt\Modules\Events\Filament\V4\Resources\RefundResource;
use Kurt\Modules\Events\Filament\V4\Resources\TicketTypeResource;
use Kurt\Modules\Events\Filament\V4\Resources\WaitlistResource;

beforeEach(function () {
    if (FilamentVersion::major() !== 4) {
        $this->markTestSkipped('Filament v4 is not installed.');
    }
});

it('dispatches the facade to the v4 plugin', function () {
    expect(EventsPlugin::make())->toBeInstanceOf(Kurt\Modules\Events\Filament\V4\EventsPlugin::class)
        ->and(EventsPlugin::make()->getId())->toBe('kurtmodules-events');
});

it('registers all eight event resources on the panel', function () {
    $resources = Filament::getPanel('admin')->getResources();

    expect($resources)
        ->toContain(EventResource::class)
        ->toContain(TicketTypeResource::class)
        ->toContain(OrderResource::class)
        ->toContain(ApplicationResource::class)
        ->toContain(DiscountCodeResource::class)
        ->toContain(DocumentVerificationResource::class)
        ->toContain(RefundResource::class)
        ->toContain(WaitlistResource::class);
});

it('registers a list route for every resource', function () {
    $uris = collect(app('router')->getRoutes()->getRoutes())
        ->map(fn ($route) => $route->uri())
        ->all();

    expect($uris)
        ->toContain('admin/events')
        ->toContain('admin/ticket-types')
        ->toContain('admin/orders')
        ->toContain('admin/applications')
        ->toContain('admin/discount-codes')
        ->toContain('admin/document-verifications')
        ->toContain('admin/refunds')
        ->toContain('admin/waitlists');
});

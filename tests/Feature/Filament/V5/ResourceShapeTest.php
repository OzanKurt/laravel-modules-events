<?php

declare(strict_types=1);

use Kurt\Modules\Core\Support\FilamentVersion;
use Kurt\Modules\Events\Filament\V5\Resources\ApplicationResource;
use Kurt\Modules\Events\Filament\V5\Resources\DiscountCodeResource;
use Kurt\Modules\Events\Filament\V5\Resources\DocumentVerificationResource;
use Kurt\Modules\Events\Filament\V5\Resources\EventResource;
use Kurt\Modules\Events\Filament\V5\Resources\OrderResource;
use Kurt\Modules\Events\Filament\V5\Resources\RefundResource;
use Kurt\Modules\Events\Filament\V5\Resources\TicketTypeResource;
use Kurt\Modules\Events\Filament\V5\Resources\WaitlistResource;

beforeEach(function () {
    if (FilamentVersion::major() !== 5) {
        $this->markTestSkipped('Filament v5 is not installed.');
    }
});

/**
 * Each resource: model target, list page key, and at least one table column.
 * Form-bearing resources additionally expose >0 form fields. WaitlistResource
 * is read-only (no form fields, list page only).
 *
 * @return array<string, array{0: class-string, 1: string, 2: bool, 3: array<int, string>}>
 */
dataset('resources', [
    'Event' => [EventResource::class, 'ListEvents', true, ['title', 'status']],
    'TicketType' => [TicketTypeResource::class, 'ListTicketTypes', true, ['name', 'mode', 'price_minor']],
    'Order' => [OrderResource::class, 'ListOrders', true, ['status', 'total_minor']],
    'Application' => [ApplicationResource::class, 'ListApplications', true, ['status', 'submitted_at']],
    'DiscountCode' => [DiscountCodeResource::class, 'ListDiscountCodes', true, ['code', 'kind']],
    'DocumentVerification' => [DocumentVerificationResource::class, 'ListDocumentVerifications', true, ['status']],
    'Refund' => [RefundResource::class, 'ListRefunds', true, ['status', 'amount_minor']],
    'Waitlist' => [WaitlistResource::class, 'ListWaitlistEntries', false, ['status', 'quantity']],
]);

it('registers an index page and exposes its key table columns', function (string $resource, string $listClass, bool $hasForm, array $columns) {
    $pageClass = $resource.'\\Pages\\'.$listClass;

    expect(array_keys($resource::getPages()))->toContain('index');

    expect(tableColumnNames($resource, $pageClass))->toContain(...$columns);
})->with('resources');

it('builds a form with at least one field for editable resources', function (string $resource, string $listClass, bool $hasForm, array $columns) {
    if (! $hasForm) {
        expect(true)->toBeTrue();

        return;
    }

    $pageClass = $resource.'\\Pages\\'.$listClass;

    expect(formFieldNames($resource, $pageClass))->not->toBeEmpty();
})->with('resources');

it('builds translatable per-locale fields for Event and TicketType', function () {
    expect(formFieldNames(EventResource::class, EventResource::class.'\\Pages\\ListEvents'))
        ->toContain('title.en', 'title.tr', 'status', 'visibility', 'starts_at', 'category_id');

    expect(formFieldNames(TicketTypeResource::class, TicketTypeResource::class.'\\Pages\\ListTicketTypes'))
        ->toContain('name.en', 'name.tr', 'event_id', 'mode', 'price_minor', 'currency');
});

it('filters the Event table by status and visibility', function () {
    expect(tableFilterNames(EventResource::class, EventResource::class.'\\Pages\\ListEvents'))
        ->toContain('status', 'visibility');
});

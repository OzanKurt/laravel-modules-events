<?php

declare(strict_types=1);

use Filament\Tables\Table;
use Kurt\Modules\Core\Support\FilamentVersion;
use Kurt\Modules\Events\Attendance\Enums\ApplicationStatus;
use Kurt\Modules\Events\Attendance\Models\Application;
use Kurt\Modules\Events\Eligibility\Enums\VerificationStatus;
use Kurt\Modules\Events\Eligibility\Models\DocumentVerification;
use Kurt\Modules\Events\Filament\V5\Resources\ApplicationResource;
use Kurt\Modules\Events\Filament\V5\Resources\ApplicationResource\Pages\ListApplications;
use Kurt\Modules\Events\Filament\V5\Resources\DocumentVerificationResource;
use Kurt\Modules\Events\Filament\V5\Resources\DocumentVerificationResource\Pages\ListDocumentVerifications;
use Kurt\Modules\Events\Filament\V5\Resources\OrderResource;
use Kurt\Modules\Events\Filament\V5\Resources\OrderResource\Pages\ListOrders;
use Kurt\Modules\Events\Filament\V5\Resources\RefundResource;
use Kurt\Modules\Events\Filament\V5\Resources\RefundResource\Pages\ListRefunds;
use Kurt\Modules\Events\Flow\Enums\RefundReason;
use Kurt\Modules\Events\Flow\Enums\RefundStatus;
use Kurt\Modules\Events\Flow\Models\Refund;
use Kurt\Modules\Events\Support\Events;
use Kurt\Modules\Events\Tests\Stubs\StubUser;
use Kurt\Modules\Events\Ticketing\Enums\OrderStatus;
use Kurt\Modules\Events\Ticketing\Models\Order;

beforeEach(function () {
    if (FilamentVersion::major() !== 5) {
        $this->markTestSkipped('Filament v5 is not installed.');
    }
});

// ---- Action presence (static introspection, no Livewire render) ----

it('exposes approve + reject row actions on the application queue', function () {
    expect(tableActionNames(ApplicationResource::class, ListApplications::class))
        ->toContain('approve', 'reject');

    $table = ApplicationResource::table(Table::make(app(ListApplications::class)));
    expect($table->getFilters()['status']->getDefaultState())->toBe(ApplicationStatus::Pending->value);
});

it('exposes verify + reject row actions on the document verification queue', function () {
    expect(tableActionNames(DocumentVerificationResource::class, ListDocumentVerifications::class))
        ->toContain('verify', 'reject');

    $table = DocumentVerificationResource::table(Table::make(app(ListDocumentVerifications::class)));
    expect($table->getFilters()['status']->getDefaultState())->toBe(VerificationStatus::Pending->value);
});

it('exposes mark-processed + mark-failed row actions on refunds, filtered to pending', function () {
    expect(tableActionNames(RefundResource::class, ListRefunds::class))
        ->toContain('markProcessed', 'markFailed');

    $table = RefundResource::table(Table::make(app(ListRefunds::class)));
    expect($table->getFilters()['status']->getDefaultState())->toBe(RefundStatus::Pending->value);
});

it('exposes a request-refund row action on orders', function () {
    expect(tableActionNames(OrderResource::class, ListOrders::class))
        ->toContain('requestRefund');
});

// ---- Action behaviour (the facade/model mutations the actions invoke) ----

it('approves an application via the facade the approve action calls', function () {
    $actor = StubUser::create(['name' => 'Organizer', 'email' => 'org@example.test']);
    $application = Application::factory()->create();

    app(Events::class)->approve($application, $actor);

    expect($application->fresh()->status)->toBe(ApplicationStatus::Approved)
        ->and($application->fresh()->decided_by)->toBe($actor->getKey());
});

it('rejects an application via the facade the reject action calls', function () {
    $actor = StubUser::create(['name' => 'Organizer', 'email' => 'org2@example.test']);
    $application = Application::factory()->create();

    app(Events::class)->reject($application, $actor, 'Does not meet criteria');

    expect($application->fresh()->status)->toBe(ApplicationStatus::Rejected)
        ->and($application->fresh()->decision_note)->toBe('Does not meet criteria');
});

it('marks a refund processed via the facade the mark-processed action calls', function () {
    $refund = Refund::factory()->create();

    app(Events::class)->markRefundProcessed($refund, 're_123');

    expect($refund->fresh()->status)->toBe(RefundStatus::Processed)
        ->and($refund->fresh()->processor_reference)->toBe('re_123');
});

it('marks a refund failed via the facade the mark-failed action calls', function () {
    $refund = Refund::factory()->create();

    app(Events::class)->markRefundFailed($refund, 'Gateway declined');

    expect($refund->fresh()->status)->toBe(RefundStatus::Failed);
});

it('requests a refund for a paid order via the facade the order action calls', function () {
    $actor = StubUser::create(['name' => 'Admin', 'email' => 'admin@example.test']);
    $order = Order::factory()->create(['status' => OrderStatus::Paid, 'total_minor' => 5000, 'currency' => 'USD']);

    $refund = app(Events::class)->requestRefund($order, $actor, RefundReason::OrganizerInitiated, 'Customer asked');

    expect($refund)->toBeInstanceOf(Refund::class)
        ->and($refund->order_id)->toBe($order->getKey());
});

it('records a document verification decision the way the verify action does', function () {
    $actor = StubUser::create(['name' => 'Reviewer', 'email' => 'rev@example.test']);
    $verification = DocumentVerification::factory()->create();

    $verification->forceFill([
        'status' => VerificationStatus::Verified,
        'decided_by' => $actor->getKey(),
        'decided_at' => now(),
    ])->save();

    expect($verification->fresh()->status)->toBe(VerificationStatus::Verified)
        ->and($verification->fresh()->decided_by)->toBe($actor->getKey());
});

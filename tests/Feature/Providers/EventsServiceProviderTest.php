<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Gate;
use Kurt\Modules\Events\Flow\Models\SaleQueueEntry;
use Kurt\Modules\Events\Flow\Models\WaitlistEntry;
use Kurt\Modules\Events\Policies\QueuePolicy;
use Kurt\Modules\Events\Policies\WaitlistPolicy;
use Kurt\Modules\Events\Support\Events as EventsService;
use Kurt\Modules\Events\Tests\Stubs\StubUser;
use Kurt\Modules\Events\Ticketing\Support\QrTokenSigner;

it('binds the Events facade-service as a singleton', function () {
    $a = app(EventsService::class);
    $b = app(EventsService::class);
    expect($a === $b)->toBeTrue();
});

it('binds the QrTokenSigner with the app key', function () {
    $signer = app(QrTokenSigner::class);
    $token = $signer->sign(1, 1);
    expect($signer->verify($token))->toMatchArray(['ticket_id' => 1, 'event_id' => 1]);
});

it('publishes the config under events key', function () {
    expect(config('events.currency'))->toBe('USD');
});

it('registers the QueuePolicy for SaleQueueEntry and resolves its abilities', function () {
    expect(Gate::getPolicyFor(SaleQueueEntry::class))->toBeInstanceOf(QueuePolicy::class);

    $owner = StubUser::create(['email' => 'owner@x.com']);
    $other = StubUser::create(['email' => 'other@x.com']);
    $entry = SaleQueueEntry::factory()->create(['user_id' => $owner->id]);

    expect(Gate::forUser($owner)->allows('leave', $entry))->toBeTrue();
    expect(Gate::forUser($other)->allows('leave', $entry))->toBeFalse();
});

it('registers the WaitlistPolicy for WaitlistEntry and resolves its abilities', function () {
    expect(Gate::getPolicyFor(WaitlistEntry::class))->toBeInstanceOf(WaitlistPolicy::class);

    $owner = StubUser::create(['email' => 'owner2@x.com']);
    $other = StubUser::create(['email' => 'other2@x.com']);
    $entry = WaitlistEntry::factory()->create(['user_id' => $owner->id]);

    expect(Gate::forUser($owner)->allows('leave', $entry))->toBeTrue();
    expect(Gate::forUser($other)->allows('leave', $entry))->toBeFalse();
});

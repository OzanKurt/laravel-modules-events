<?php

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Event;
use Kurt\Modules\Events\Console\Commands\ExpireWaitlistClaimsCommand;
use Kurt\Modules\Events\Flow\Enums\WaitlistStatus;
use Kurt\Modules\Events\Flow\Events\WaitlistExpired;
use Kurt\Modules\Events\Flow\Events\WaitlistPromoted;
use Kurt\Modules\Events\Flow\Models\WaitlistEntry;
use Kurt\Modules\Events\Flow\Support\WaitlistPromoter;
use Kurt\Modules\Events\Ticketing\Models\TicketType;

beforeEach(function () {
    app(Kernel::class)->registerCommand(new ExpireWaitlistClaimsCommand);
});

it('expires offered entries past claim window and promotes next', function () {
    Event::fake([WaitlistExpired::class, WaitlistPromoted::class]);

    $type = TicketType::factory()->create();
    $offered = WaitlistEntry::factory()->create([
        'ticket_type_id' => $type->id,
        'user_id' => 1,
        'status' => WaitlistStatus::Offered,
        'claim_expires_at' => now()->subMinute(),
    ]);
    $waiting = WaitlistEntry::factory()->create([
        'ticket_type_id' => $type->id,
        'user_id' => 2,
        'status' => WaitlistStatus::Waiting,
    ]);

    $this->app->instance(WaitlistPromoter::class, new WaitlistPromoter(app('config')));
    $exit = Artisan::call('events:expire-waitlist-claims');

    expect($exit)->toBe(0);

    $offered->refresh();
    expect($offered->status)->toBe(WaitlistStatus::Expired);

    $waiting->refresh();
    expect($waiting->status)->toBe(WaitlistStatus::Offered);

    Event::assertDispatched(WaitlistExpired::class);
    Event::assertDispatched(WaitlistPromoted::class);
});

it('does nothing for offered entries still inside claim window', function () {
    $type = TicketType::factory()->create();
    $offered = WaitlistEntry::factory()->create([
        'ticket_type_id' => $type->id,
        'user_id' => 1,
        'status' => WaitlistStatus::Offered,
        'claim_expires_at' => now()->addMinute(),
    ]);

    $this->app->instance(WaitlistPromoter::class, new WaitlistPromoter(app('config')));
    Artisan::call('events:expire-waitlist-claims');

    $offered->refresh();
    expect($offered->status)->toBe(WaitlistStatus::Offered);
});

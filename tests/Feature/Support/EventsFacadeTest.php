<?php

declare(strict_types=1);

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Kurt\Modules\Events\Attendance\Enums\AnnouncementAudience;
use Kurt\Modules\Events\Attendance\Enums\ApplicationStatus;
use Kurt\Modules\Events\Attendance\Enums\AttendeeStatus;
use Kurt\Modules\Events\Attendance\Events\AnnouncementSent;
use Kurt\Modules\Events\Attendance\Events\ApplicationApproved;
use Kurt\Modules\Events\Attendance\Events\ApplicationRejected;
use Kurt\Modules\Events\Attendance\Events\ApplicationSubmitted;
use Kurt\Modules\Events\Attendance\Models\Attendee;
use Kurt\Modules\Events\Attendance\Support\AnnouncementDispatcher;
use Kurt\Modules\Events\Catalog\Enums\EventStatus;
use Kurt\Modules\Events\Catalog\Enums\EventVisibility;
use Kurt\Modules\Events\Catalog\Enums\OrganizerRole;
use Kurt\Modules\Events\Catalog\Events\EventApprovedForPublication;
use Kurt\Modules\Events\Catalog\Events\EventCancelled;
use Kurt\Modules\Events\Catalog\Events\EventCompleted;
use Kurt\Modules\Events\Catalog\Events\EventCreated;
use Kurt\Modules\Events\Catalog\Events\EventPublished;
use Kurt\Modules\Events\Catalog\Models\Event as CatalogEvent;
use Kurt\Modules\Events\Catalog\Support\EventCloner;
use Kurt\Modules\Events\Catalog\Support\TemplateManager;
use Kurt\Modules\Events\Flow\Enums\PayoutStatus;
use Kurt\Modules\Events\Flow\Enums\QueueStatus;
use Kurt\Modules\Events\Flow\Enums\RefundReason;
use Kurt\Modules\Events\Flow\Enums\RefundStatus;
use Kurt\Modules\Events\Flow\Enums\WaitlistStatus;
use Kurt\Modules\Events\Flow\Models\PayoutLedgerEntry;
use Kurt\Modules\Events\Flow\Models\SaleQueueEntry;
use Kurt\Modules\Events\Flow\Models\WaitlistEntry;
use Kurt\Modules\Events\Flow\Support\ActiveQueueChallengeProvider;
use Kurt\Modules\Events\Flow\Support\GdprAnonymizer;
use Kurt\Modules\Events\Flow\Support\GdprExporter;
use Kurt\Modules\Events\Flow\Support\PayoutAccruer;
use Kurt\Modules\Events\Flow\Support\RefundCoordinator;
use Kurt\Modules\Events\Flow\Support\SponsorCoordinator;
use Kurt\Modules\Events\Support\Events as EventsService;
use Kurt\Modules\Events\Tests\Stubs\StubUser;
use Kurt\Modules\Events\Ticketing\Enums\OrderStatus;
use Kurt\Modules\Events\Ticketing\Enums\TicketStatus;
use Kurt\Modules\Events\Ticketing\Events\OrderCreated;
use Kurt\Modules\Events\Ticketing\Events\OrderPaid;
use Kurt\Modules\Events\Ticketing\Events\ReferralAttributionRecorded;
use Kurt\Modules\Events\Ticketing\Events\TicketCheckedIn;
use Kurt\Modules\Events\Ticketing\Events\TicketIssued;
use Kurt\Modules\Events\Ticketing\Exceptions\TicketTypeSoldOut;
use Kurt\Modules\Events\Ticketing\Models\Order;
use Kurt\Modules\Events\Ticketing\Models\ReferralLink;
use Kurt\Modules\Events\Ticketing\Models\Ticket;
use Kurt\Modules\Events\Ticketing\Models\TicketType;
use Kurt\Modules\Events\Ticketing\Support\PriceCalculator;
use Kurt\Modules\Events\Ticketing\Support\QrTokenSigner;
use Kurt\Modules\Events\Ticketing\Support\TransferEngine;

function eventsService(): EventsService
{
    return new EventsService(
        prices: new PriceCalculator,
        transferEngine: new TransferEngine,
        signer: new QrTokenSigner('test-secret-key'),
        refunds: new RefundCoordinator(app('config')),
        payouts: new PayoutAccruer,
        sponsors: new SponsorCoordinator(new QrTokenSigner('test-secret-key')),
        cloner: new EventCloner,
        templates: new TemplateManager,
        gdprExporter: new GdprExporter,
        gdprAnonymizer: new GdprAnonymizer(app('config')),
        announcements: new AnnouncementDispatcher(app('config')),
        queueChallenge: new ActiveQueueChallengeProvider,
    );
}

beforeEach(function () {
    config()->set('kurtmodules.user_model', StubUser::class);
});

it('createEvent inserts an event and assigns the organizer as owner', function () {
    Event::fake([EventCreated::class]);
    $organizer = StubUser::create(['email' => 'org@example.com']);

    $event = eventsService()->createEvent([
        'slug' => 'facade-test',
        'title' => ['en' => 'Facade Test'],
        'visibility' => EventVisibility::Public,
        'starts_at' => now()->addDays(7),
        'ends_at' => now()->addDays(7)->addHour(),
        'timezone' => 'UTC',
    ], $organizer);

    expect($event->id)->not->toBeNull();
    expect($event->status)->toBe(EventStatus::Draft);
    expect($event->organizers()->where('user_id', $organizer->id)->where('role', OrganizerRole::Owner->value)->exists())->toBeTrue();

    Event::assertDispatched(EventCreated::class);
});

it('createEvent puts event in pending approval when require_approval=true', function () {
    config()->set('events.publishing.require_approval', true);
    $organizer = StubUser::create(['email' => 'o@example.com']);

    $event = eventsService()->createEvent([
        'slug' => 'pending-test',
        'title' => ['en' => 'Pending'],
        'visibility' => EventVisibility::Public,
        'starts_at' => now()->addDays(2),
        'ends_at' => now()->addDays(2)->addHour(),
    ], $organizer);

    expect($event->status)->toBe(EventStatus::PendingApproval);
});

it('approveForPublication flips PendingApproval -> Draft and dispatches event', function () {
    Event::fake([EventApprovedForPublication::class]);
    $admin = StubUser::create(['email' => 'admin@example.com']);
    $event = CatalogEvent::factory()->create(['status' => EventStatus::PendingApproval]);

    eventsService()->approveForPublication($event, $admin);

    $event->refresh();
    expect($event->status)->toBe(EventStatus::Draft);

    Event::assertDispatched(EventApprovedForPublication::class);
});

it('publish flips status to Published and dispatches event', function () {
    Event::fake([EventPublished::class]);
    $event = CatalogEvent::factory()->create(['status' => EventStatus::Draft]);

    eventsService()->publish($event);

    $event->refresh();
    expect($event->status)->toBe(EventStatus::Published);
    Event::assertDispatched(EventPublished::class);
});

it('cancel sets cancelled_at, cancelled_by and reason', function () {
    Event::fake([EventCancelled::class]);
    $canceller = StubUser::create(['email' => 'c@example.com']);
    $event = CatalogEvent::factory()->create();

    eventsService()->cancel($event, $canceller, 'No funding');

    $event->refresh();
    expect($event->status)->toBe(EventStatus::Cancelled);
    expect($event->cancelled_at)->not->toBeNull();
    expect($event->cancelled_by)->toBe($canceller->id);
    expect($event->cancellation_reason)->toBe('No funding');

    Event::assertDispatched(EventCancelled::class);
});

it('complete dispatches EventCompleted', function () {
    Event::fake([EventCompleted::class]);
    $event = CatalogEvent::factory()->create();

    eventsService()->complete($event);

    $event->refresh();
    expect($event->status)->toBe(EventStatus::Completed);
    Event::assertDispatched(EventCompleted::class);
});

it('reserve happy path with three holder assignments', function () {
    Event::fake([OrderCreated::class]);
    $buyer = StubUser::create(['email' => 'b@example.com']);
    $catalogEvent = CatalogEvent::factory()->create();
    $type = TicketType::factory()->create([
        'event_id' => $catalogEvent->id,
        'price_minor' => 1500,
        'currency' => 'USD',
        'capacity' => 10,
        'sold_count' => 0,
    ]);

    $order = eventsService()->reserve($type, $buyer, 3, [
        ['name' => 'Alice', 'email' => 'a@x.com'],
        ['name' => 'Bob', 'email' => 'b@x.com'],
        ['name' => 'Carol', 'email' => 'c@x.com'],
    ]);

    expect($order->status)->toBe(OrderStatus::Pending);
    expect($order->total_minor)->toBe(4500);
    expect($order->items()->count())->toBe(1);
    expect($order->items()->first()->assignments()->count())->toBe(3);

    $type->refresh();
    expect($type->sold_count)->toBe(3);
    Event::assertDispatched(OrderCreated::class);
});

it('reserve throws TicketTypeSoldOut when capacity is exceeded', function () {
    $buyer = StubUser::create(['email' => 'b@example.com']);
    $type = TicketType::factory()->create([
        'capacity' => 2,
        'sold_count' => 1,
    ]);

    eventsService()->reserve($type, $buyer, 3, [
        ['name' => 'A', 'email' => 'a@x.com'],
        ['name' => 'B', 'email' => 'b@x.com'],
        ['name' => 'C', 'email' => 'c@x.com'],
    ]);
})->throws(TicketTypeSoldOut::class);

it('pay issues a ticket per assignment with a signed QR token', function () {
    Event::fake([OrderPaid::class, TicketIssued::class]);
    $buyer = StubUser::create(['email' => 'b@example.com']);
    $type = TicketType::factory()->create(['price_minor' => 500, 'currency' => 'USD']);

    $order = eventsService()->reserve($type, $buyer, 2, [
        ['name' => 'A', 'email' => 'a@x.com'],
        ['name' => 'B', 'email' => 'b@x.com'],
    ]);
    eventsService()->pay($order, 'stripe', 'pi_123');

    $order->refresh();
    expect($order->status)->toBe(OrderStatus::Paid);
    expect($order->processor)->toBe('stripe');
    expect($order->processor_reference)->toBe('pi_123');
    expect(Ticket::query()->where('event_id', $order->event_id)->count())->toBe(2);

    $signer = new QrTokenSigner('test-secret-key');
    foreach (Ticket::query()->where('event_id', $order->event_id)->get() as $ticket) {
        $payload = $signer->verify($ticket->qr_token);
        expect($payload['ticket_id'])->toBe($ticket->id);
    }

    Event::assertDispatched(OrderPaid::class);
    Event::assertDispatchedTimes(TicketIssued::class, 2);
});

it('transferTicket swaps the holder when free', function () {
    $orig = StubUser::create(['email' => 'orig@example.com']);
    $new = StubUser::create(['name' => 'Newby', 'email' => 'new@example.com']);
    $type = TicketType::factory()->create(['transferable' => true, 'transfer_fee_minor' => null]);
    $event = $type->event()->first();
    $ticket = Ticket::factory()->create([
        'ticket_type_id' => $type->id,
        'event_id' => $event->id,
        'holder_id' => $orig->id,
    ]);

    $updated = eventsService()->transferTicket($ticket, $new);

    expect($updated->holder_id)->toBe($new->id);
    expect($updated->holder_email)->toBe('new@example.com');
});

it('checkIn marks the ticket and dispatches event', function () {
    Event::fake([TicketCheckedIn::class]);
    $scanner = StubUser::create(['email' => 's@example.com']);
    $ticket = Ticket::factory()->create(['status' => TicketStatus::Issued]);

    $result = eventsService()->checkIn($ticket, $scanner);

    expect($result->status)->toBe(TicketStatus::CheckedIn);
    expect($result->checked_in_by)->toBe($scanner->id);
    Event::assertDispatched(TicketCheckedIn::class);
});

it('checkInByToken verifies QR signature and checks the ticket in', function () {
    $scanner = StubUser::create(['email' => 's@example.com']);
    $ticket = Ticket::factory()->create(['status' => TicketStatus::Issued]);

    $token = (new QrTokenSigner('test-secret-key'))->sign($ticket->id, $ticket->event_id);
    $result = eventsService()->checkInByToken($token, $scanner);

    expect($result->id)->toBe($ticket->id);
    expect($result->status)->toBe(TicketStatus::CheckedIn);
});

it('apply, approve, and reject flows', function () {
    Event::fake([ApplicationSubmitted::class, ApplicationApproved::class, ApplicationRejected::class]);
    $applicant = StubUser::create(['email' => 'a@example.com']);
    $organizer = StubUser::create(['email' => 'o@example.com']);
    $type1 = TicketType::factory()->create();
    $type2 = TicketType::factory()->create();

    $app = eventsService()->apply($type1, $applicant, ['why' => 'I love events']);
    expect($app->status)->toBe(ApplicationStatus::Pending);

    eventsService()->approve($app, $organizer);
    $app->refresh();
    expect($app->status)->toBe(ApplicationStatus::Approved);

    $other = eventsService()->apply($type2, $applicant, []);
    eventsService()->reject($other, $organizer, 'No spots');
    $other->refresh();
    expect($other->status)->toBe(ApplicationStatus::Rejected);
    expect($other->decision_note)->toBe('No spots');

    Event::assertDispatched(ApplicationSubmitted::class);
    Event::assertDispatched(ApplicationApproved::class);
    Event::assertDispatched(ApplicationRejected::class);
});

it('requestRefund + markRefundProcessed end-to-end', function () {
    $buyer = StubUser::create(['email' => 'b@example.com']);
    $order = Order::factory()->paid()->create([
        'user_id' => $buyer->id,
        'total_minor' => 1500,
    ]);

    $refund = eventsService()->requestRefund($order, $buyer, RefundReason::AttendeeRequest, 'Changed plans');
    expect($refund->status)->toBe(RefundStatus::Pending);

    eventsService()->markRefundProcessed($refund, 'rfd_1');
    $refund->refresh();
    expect($refund->status)->toBe(RefundStatus::Processed);
    expect($refund->processor_reference)->toBe('rfd_1');
});

it('joinQueue + queueHeartbeat', function () {
    Carbon::setTestNow(now()->copy()->subSeconds(10));
    $user = StubUser::create(['email' => 'u@example.com']);
    $catalogEvent = CatalogEvent::factory()->create();

    $entry = eventsService()->joinQueue($catalogEvent, $user);
    expect($entry->status)->toBe(QueueStatus::Waiting);
    expect($entry->position)->toBe(1);

    $earlier = $entry->last_heartbeat_at?->copy();
    Carbon::setTestNow(now()->copy()->addSeconds(20));
    eventsService()->queueHeartbeat($entry);
    $entry->refresh();
    expect($entry->last_heartbeat_at?->toIso8601String())->not->toBe($earlier?->toIso8601String());

    Carbon::setTestNow();
});

it('joinWaitlist + claimWaitlist reserves a ticket', function () {
    Event::fake([OrderCreated::class]);
    $user = StubUser::create(['name' => 'Wait Person', 'email' => 'w@example.com']);
    $type = TicketType::factory()->create();

    $entry = eventsService()->joinWaitlist($type, $user, 1);
    expect($entry->status)->toBe(WaitlistStatus::Waiting);

    // The entry must be a live offer before it can be claimed.
    $entry->forceFill([
        'status' => WaitlistStatus::Offered,
        'offered_at' => now(),
        'claim_expires_at' => now()->addMinutes(10),
    ])->save();

    $order = eventsService()->claimWaitlist($entry);
    $entry->refresh();
    expect($entry->status)->toBe(WaitlistStatus::Claimed);
    expect($order->total_minor)->toBe($type->price_minor);
    Event::assertDispatched(OrderCreated::class);
});

it('cloneEvent delegates to EventCloner', function () {
    $source = CatalogEvent::factory()->create();
    $clone = eventsService()->cloneEvent($source, ['timezone' => 'Europe/Berlin']);

    expect($clone->id)->not->toBe($source->id);
    expect($clone->timezone)->toBe('Europe/Berlin');
});

it('announce sends immediately when scheduled_for is null', function () {
    Event::fake([AnnouncementSent::class]);
    $author = StubUser::create(['email' => 'auth@example.com']);
    $catalogEvent = CatalogEvent::factory()->create();
    Attendee::factory()->create([
        'event_id' => $catalogEvent->id,
        'user_id' => 1,
        'status' => AttendeeStatus::Registered,
    ]);

    $announcement = eventsService()->announce($catalogEvent, $author, 'Hi', 'Body');

    $announcement->refresh();
    expect($announcement->sent_at)->not->toBeNull();
    Event::assertDispatched(AnnouncementSent::class);
});

it('announce stores a future-scheduled announcement without dispatching', function () {
    Event::fake([AnnouncementSent::class]);
    $author = StubUser::create(['email' => 'a@example.com']);
    $catalogEvent = CatalogEvent::factory()->create();

    $announcement = eventsService()->announce(
        $catalogEvent,
        $author,
        'Later',
        'See you',
        AnnouncementAudience::All,
        [],
        now()->addHour(),
    );

    expect($announcement->scheduled_for)->not->toBeNull();
    expect($announcement->sent_at)->toBeNull();
    Event::assertNotDispatched(AnnouncementSent::class);
});

it('attributeReferral sets referral_link_id on the order', function () {
    Event::fake([ReferralAttributionRecorded::class]);
    $organizer = StubUser::create(['email' => 'org@example.com']);
    $order = Order::factory()->create();
    $link = ReferralLink::factory()->create([
        'event_id' => $order->event_id,
        'organizer_id' => $organizer->id,
        'code' => 'REF1',
        'active' => true,
        'uses_count' => 0,
    ]);

    eventsService()->attributeReferral($order, 'REF1');

    $order->refresh();
    expect($order->referral_link_id)->toBe($link->id);
    $link->refresh();
    expect($link->uses_count)->toBe(1);
    Event::assertDispatched(ReferralAttributionRecorded::class);
});

it('exportPersonalData returns expected shape', function () {
    $user = StubUser::create(['email' => 'u@example.com']);
    $data = eventsService()->exportPersonalData($user);

    expect($data)->toHaveKeys([
        'user_id', 'attendees', 'applications', 'orders',
        'order_item_assignments', 'tickets', 'refunds_as_requester',
        'document_uploads', 'audit_log_as_actor', 'sale_queue_entries',
        'waitlist_entries',
    ]);
});

it('anonymizePersonalData replaces PII on tickets and assignments', function () {
    $user = StubUser::create(['email' => 'u@example.com']);
    $ticket = Ticket::factory()->create([
        'holder_id' => $user->id,
        'holder_name' => 'Real Name',
        'holder_email' => 'real@example.com',
    ]);

    eventsService()->anonymizePersonalData($user);

    $ticket->refresh();
    expect($ticket->holder_name)->not->toBe('Real Name');
    expect($ticket->holder_email)->not->toBe('real@example.com');
});

it('recordPayout flips ledger entry to PaidOut with reference', function () {
    $order = Order::factory()->create();
    $entry = PayoutLedgerEntry::create([
        'order_id' => $order->id,
        'organizer_user_id' => 1,
        'share_basis_points' => 500,
        'amount_minor' => 250,
        'currency' => 'USD',
        'status' => PayoutStatus::Accrued,
    ]);

    eventsService()->recordPayout($entry->id, 'po_1');

    $entry->refresh();
    expect($entry->status)->toBe(PayoutStatus::PaidOut);
    expect($entry->payout_reference)->toBe('po_1');
});

it('reversePayout marks the entry Reversed', function () {
    $order = Order::factory()->create();
    $entry = PayoutLedgerEntry::create([
        'order_id' => $order->id,
        'organizer_user_id' => 1,
        'share_basis_points' => 500,
        'amount_minor' => 250,
        'currency' => 'USD',
        'status' => PayoutStatus::PaidOut,
    ]);

    eventsService()->reversePayout($entry->id, 'Refunded');

    $entry->refresh();
    expect($entry->status)->toBe(PayoutStatus::Reversed);
});

it('joinQueue is idempotent and respects existing position', function () {
    $user = StubUser::create(['email' => 'u@example.com']);
    $catalogEvent = CatalogEvent::factory()->create();

    SaleQueueEntry::factory()->create([
        'event_id' => $catalogEvent->id,
        'user_id' => 999,
        'position' => 5,
    ]);

    $entry = eventsService()->joinQueue($catalogEvent, $user);
    expect($entry->position)->toBe(6);

    $second = eventsService()->joinQueue($catalogEvent, $user);
    expect($second->id)->toBe($entry->id);
});

it('joinWaitlist is idempotent for the same user + type pair', function () {
    $user = StubUser::create(['email' => 'u@example.com']);
    $type = TicketType::factory()->create();

    $a = eventsService()->joinWaitlist($type, $user, 1);
    $b = eventsService()->joinWaitlist($type, $user, 1);
    expect($a->id)->toBe($b->id);
    expect(WaitlistEntry::query()->where('user_id', $user->id)->count())->toBe(1);
});

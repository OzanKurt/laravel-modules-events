<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Support;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Kurt\Modules\Events\Attendance\Enums\AnnouncementAudience;
use Kurt\Modules\Events\Attendance\Enums\ApplicationStatus;
use Kurt\Modules\Events\Attendance\Events\ApplicationApproved;
use Kurt\Modules\Events\Attendance\Events\ApplicationRejected;
use Kurt\Modules\Events\Attendance\Events\ApplicationSubmitted;
use Kurt\Modules\Events\Attendance\Models\Announcement;
use Kurt\Modules\Events\Attendance\Models\Application;
use Kurt\Modules\Events\Attendance\Support\AnnouncementDispatcher;
use Kurt\Modules\Events\Catalog\Enums\EventStatus;
use Kurt\Modules\Events\Catalog\Enums\OrganizerRole;
use Kurt\Modules\Events\Catalog\Events\EventApprovedForPublication;
use Kurt\Modules\Events\Catalog\Events\EventCancelled;
use Kurt\Modules\Events\Catalog\Events\EventCompleted;
use Kurt\Modules\Events\Catalog\Events\EventCreated;
use Kurt\Modules\Events\Catalog\Events\EventPublished;
use Kurt\Modules\Events\Catalog\Models\Event;
use Kurt\Modules\Events\Catalog\Models\EventTemplate;
use Kurt\Modules\Events\Catalog\Support\EventCloner;
use Kurt\Modules\Events\Catalog\Support\TemplateManager;
use Kurt\Modules\Events\Flow\Enums\PayoutStatus;
use Kurt\Modules\Events\Flow\Enums\QueueStatus;
use Kurt\Modules\Events\Flow\Enums\RefundReason;
use Kurt\Modules\Events\Flow\Enums\WaitlistStatus;
use Kurt\Modules\Events\Flow\Models\PayoutLedgerEntry;
use Kurt\Modules\Events\Flow\Models\Refund;
use Kurt\Modules\Events\Flow\Models\SaleQueueEntry;
use Kurt\Modules\Events\Flow\Models\Sponsor;
use Kurt\Modules\Events\Flow\Models\SponsorTier;
use Kurt\Modules\Events\Flow\Models\WaitlistEntry;
use Kurt\Modules\Events\Flow\Support\GdprAnonymizer;
use Kurt\Modules\Events\Flow\Support\GdprExporter;
use Kurt\Modules\Events\Flow\Support\PayoutAccruer;
use Kurt\Modules\Events\Flow\Support\RefundCoordinator;
use Kurt\Modules\Events\Flow\Support\SponsorCoordinator;
use Kurt\Modules\Events\Ticketing\Enums\OrderStatus;
use Kurt\Modules\Events\Ticketing\Enums\TicketStatus;
use Kurt\Modules\Events\Ticketing\Events\OrderCreated;
use Kurt\Modules\Events\Ticketing\Events\ReferralAttributionRecorded;
use Kurt\Modules\Events\Ticketing\Events\TicketCheckedIn;
use Kurt\Modules\Events\Ticketing\Events\TicketIssued;
use Kurt\Modules\Events\Ticketing\Exceptions\TicketTypeSoldOut;
use Kurt\Modules\Events\Ticketing\Models\DiscountCode;
use Kurt\Modules\Events\Ticketing\Models\Order;
use Kurt\Modules\Events\Ticketing\Models\OrderItem;
use Kurt\Modules\Events\Ticketing\Models\ReferralLink;
use Kurt\Modules\Events\Ticketing\Models\Ticket;
use Kurt\Modules\Events\Ticketing\Models\TicketType;
use Kurt\Modules\Events\Ticketing\Support\DraftOrder;
use Kurt\Modules\Events\Ticketing\Support\PriceCalculator;
use Kurt\Modules\Events\Ticketing\Support\QrTokenSigner;
use Kurt\Modules\Events\Ticketing\Support\TransferEngine;

final class Events
{
    public function __construct(
        private readonly PriceCalculator $prices,
        private readonly TransferEngine $transferEngine,
        private readonly QrTokenSigner $signer,
        private readonly RefundCoordinator $refunds,
        private readonly PayoutAccruer $payouts,
        private readonly SponsorCoordinator $sponsors,
        private readonly EventCloner $cloner,
        private readonly TemplateManager $templates,
        private readonly GdprExporter $gdprExporter,
        private readonly GdprAnonymizer $gdprAnonymizer,
        private readonly AnnouncementDispatcher $announcements,
    ) {}

    // ===== Catalog =====

    /** @param array<string, mixed> $data */
    public function createEvent(array $data, Model $organizer): Event
    {
        return DB::transaction(function () use ($data, $organizer): Event {
            $status = (bool) config('events.publishing.require_approval', false)
                ? EventStatus::PendingApproval
                : EventStatus::Draft;

            $event = Event::create(array_merge($data, ['status' => $status]));
            $event->organizers()->create([
                'user_id' => $organizer->getKey(),
                'role' => OrganizerRole::Owner,
            ]);
            EventCreated::dispatch($event);

            return $event;
        });
    }

    public function approveForPublication(Event $event, Model $platformAdmin): void
    {
        if ($event->status !== EventStatus::PendingApproval) {
            throw new \RuntimeException('Event not pending approval');
        }
        $event->forceFill(['status' => EventStatus::Draft])->save();
        EventApprovedForPublication::dispatch($event, $platformAdmin);
    }

    public function publish(Event $event): void
    {
        $event->forceFill(['status' => EventStatus::Published])->save();
        EventPublished::dispatch($event);
    }

    public function cancel(Event $event, Model $canceller, string $reason): void
    {
        $event->forceFill([
            'status' => EventStatus::Cancelled,
            'cancelled_at' => now(),
            'cancelled_by' => $canceller->getKey(),
            'cancellation_reason' => $reason,
        ])->save();
        EventCancelled::dispatch($event, $canceller, $reason);
    }

    public function complete(Event $event): void
    {
        $event->forceFill(['status' => EventStatus::Completed])->save();
        EventCompleted::dispatch($event);
    }

    // ===== Ticketing =====

    /**
     * @param  array<int, array{name: string, email: string, user_id?: int|string|null, metadata?: array<string, mixed>}>  $holderAssignments
     */
    public function reserve(
        TicketType $type,
        Model $buyer,
        int $quantity,
        array $holderAssignments,
        ?string $discountCode = null,
        ?int $unitPriceMinorOverride = null,
    ): Order {
        return DB::transaction(function () use ($type, $buyer, $quantity, $holderAssignments, $discountCode, $unitPriceMinorOverride): Order {
            /** @var TicketType $type */
            $type = TicketType::query()->lockForUpdate()->findOrFail($type->id);

            if (count($holderAssignments) !== $quantity) {
                throw new \InvalidArgumentException('Assignment count mismatch');
            }

            if ($type->capacity !== null && ($type->capacity - $type->sold_count) < $quantity) {
                throw new TicketTypeSoldOut;
            }

            $unitPrice = $unitPriceMinorOverride ?? $type->currentUnitPriceMinor();
            if ($type->minimum_price_minor !== null && $unitPrice < $type->minimum_price_minor) {
                throw new \InvalidArgumentException('Below minimum price');
            }

            $event = $type->event()->firstOrFail();

            $draft = new DraftOrder(
                event: $event,
                buyer: $buyer,
                currency: $type->currency,
                items: [['ticket_type_id' => $type->id, 'quantity' => $quantity, 'unit_price_minor' => $unitPrice]],
            );

            $code = $discountCode !== null
                ? DiscountCode::query()->where('code', $discountCode)->first()
                : null;

            $breakdown = $this->prices->apply($draft, $code);

            $order = Order::create([
                'event_id' => $type->event_id,
                'user_id' => $buyer->getKey(),
                'status' => OrderStatus::Pending,
                'subtotal_minor' => $breakdown->subtotalMinor,
                'discount_minor' => $breakdown->discountMinor,
                'tax_minor' => 0,
                'total_minor' => $breakdown->totalMinor,
                'currency' => $breakdown->currency,
                'discount_code_id' => $code?->id,
            ]);

            /** @var OrderItem $orderItem */
            $orderItem = $order->items()->create([
                'ticket_type_id' => $type->id,
                'price_tier_id' => $type->activePriceTier()?->id,
                'quantity' => $quantity,
                'unit_price_minor' => $unitPrice,
                'line_total_minor' => $unitPrice * $quantity,
            ]);

            foreach ($holderAssignments as $i => $assignment) {
                $orderItem->assignments()->create([
                    'seat_index' => $i,
                    'holder_user_id' => $assignment['user_id'] ?? null,
                    'holder_name' => $assignment['name'],
                    'holder_email' => $assignment['email'],
                    'holder_metadata' => $assignment['metadata'] ?? null,
                ]);
            }

            // Reserved capacity is tracked at the ticket-type level. The event-level
            // tickets_sold_count is the count of *issued* tickets and is maintained
            // solely by TicketObserver when tickets are created/cancelled, so it is
            // deliberately not incremented here at reservation time.
            $type->increment('sold_count', $quantity);

            OrderCreated::dispatch($order);

            return $order;
        });
    }

    public function pay(Order $order, string $processor, string $reference): void
    {
        $order->forceFill([
            'status' => OrderStatus::Paid,
            'processor' => $processor,
            'processor_reference' => $reference,
            'paid_at' => now(),
        ])->save();

        foreach ($order->items()->with('assignments', 'ticketType')->get() as $item) {
            foreach ($item->assignments as $assignment) {
                /** @var Ticket $ticket */
                $ticket = Ticket::create([
                    'order_item_id' => $item->id,
                    'order_item_assignment_id' => $assignment->id,
                    'ticket_type_id' => $item->ticket_type_id,
                    'event_id' => $order->event_id,
                    'holder_id' => $assignment->holder_user_id,
                    'holder_name' => $assignment->holder_name,
                    'holder_email' => $assignment->holder_email,
                    'status' => TicketStatus::Issued,
                    'qr_token' => 'placeholder',
                ]);
                $ticket->forceFill(['qr_token' => $this->signer->sign($ticket->id, $order->event_id)])->save();
                TicketIssued::dispatch($ticket);
            }
        }

        $order->forceFill(['assignment_completed_at' => now()])->save();
        // OrderPaid is dispatched exactly once, by OrderObserver, when the order's
        // status transitions to Paid (set above). Do not dispatch it here as well.
    }

    public function transferTicket(Ticket $ticket, Model $newHolder): Ticket
    {
        return $this->transferEngine->attemptTransfer($ticket, $newHolder);
    }

    public function checkIn(Ticket $ticket, Model $scanner): Ticket
    {
        $ticket->forceFill([
            'status' => TicketStatus::CheckedIn,
            'checked_in_at' => now(),
            'checked_in_by' => $scanner->getKey(),
        ])->save();
        TicketCheckedIn::dispatch($ticket, $scanner);

        return $ticket;
    }

    public function checkInByToken(string $qrToken, Model $scanner): Ticket
    {
        $payload = $this->signer->verify($qrToken);
        $ticket = Ticket::query()->findOrFail($payload['ticket_id']);

        return $this->checkIn($ticket, $scanner);
    }

    // ===== Applications =====

    /** @param array<string, mixed> $formAnswers */
    public function apply(TicketType $type, Model $applicant, array $formAnswers = []): Application
    {
        $app = Application::create([
            'event_id' => $type->event_id,
            'ticket_type_id' => $type->id,
            'applicant_id' => $applicant->getKey(),
            'status' => ApplicationStatus::Pending,
            'submitted_at' => now(),
            'metadata' => ['form_answers' => $formAnswers],
        ]);
        ApplicationSubmitted::dispatch($app);

        return $app;
    }

    public function approve(Application $application, Model $approver): Application
    {
        $application->forceFill([
            'status' => ApplicationStatus::Approved,
            'decided_at' => now(),
            'decided_by' => $approver->getKey(),
        ])->save();
        ApplicationApproved::dispatch($application, $approver);

        return $application;
    }

    public function reject(Application $application, Model $rejector, string $reason): ?Refund
    {
        $application->forceFill([
            'status' => ApplicationStatus::Rejected,
            'decided_at' => now(),
            'decided_by' => $rejector->getKey(),
            'decision_note' => $reason,
        ])->save();
        ApplicationRejected::dispatch($application, $rejector, $reason);

        if ($application->reservation_order_id !== null) {
            $order = Order::find($application->reservation_order_id);
            if ($order !== null && $order->status === OrderStatus::Paid) {
                return $this->refunds->request($order, $rejector, RefundReason::Rejection, $reason);
            }
        }

        return null;
    }

    // ===== Refunds =====

    public function requestRefund(Order|Ticket $target, Model $requester, RefundReason $reason, ?string $note = null): Refund
    {
        return $this->refunds->request($target, $requester, $reason, $note);
    }

    public function markRefundProcessed(Refund $refund, string $processorReference): void
    {
        $this->refunds->markProcessed($refund, $processorReference);
    }

    public function markRefundFailed(Refund $refund, string $note): void
    {
        $this->refunds->markFailed($refund, $note);
    }

    public function cancelOrderByBuyer(Order $order, Model $buyer): Refund
    {
        return $this->refunds->cancelOrderByBuyer($order, $buyer);
    }

    // ===== Queue + Waitlist =====

    public function joinQueue(Event $event, Model $user): SaleQueueEntry
    {
        $maxPosition = (int) SaleQueueEntry::query()->where('event_id', $event->id)->max('position');

        return SaleQueueEntry::firstOrCreate(
            ['event_id' => $event->id, 'user_id' => $user->getKey()],
            [
                'joined_at' => now(),
                'position' => $maxPosition + 1,
                'last_heartbeat_at' => now(),
                'status' => QueueStatus::Waiting,
            ],
        );
    }

    public function queueHeartbeat(SaleQueueEntry $entry): void
    {
        $entry->forceFill(['last_heartbeat_at' => now()])->save();
    }

    public function joinWaitlist(TicketType $type, Model $user, int $quantity = 1): WaitlistEntry
    {
        return WaitlistEntry::firstOrCreate(
            ['ticket_type_id' => $type->id, 'user_id' => $user->getKey()],
            [
                'quantity' => $quantity,
                'status' => WaitlistStatus::Waiting,
            ],
        );
    }

    public function claimWaitlist(WaitlistEntry $entry): Order
    {
        $entry->forceFill(['status' => WaitlistStatus::Claimed])->save();
        $user = $entry->user()->firstOrFail();
        $type = $entry->ticketType()->firstOrFail();

        return $this->reserve($type, $user, $entry->quantity, array_fill(0, $entry->quantity, [
            'name' => (string) ($user->getAttribute('name') ?? 'Waitlist Claimant'),
            'email' => (string) ($user->getAttribute('email') ?? ''),
            'user_id' => $user->getKey(),
        ]));
    }

    // ===== Cloning + templates =====

    /** @param array<string, mixed> $overrides */
    public function cloneEvent(Event $source, array $overrides = []): Event
    {
        return $this->cloner->clone($source, $overrides);
    }

    public function saveAsTemplate(Event $source, Model $owner, string $name, ?string $slug = null, bool $public = false): EventTemplate
    {
        return $this->templates->saveAs($source, $owner, $name, $slug, $public);
    }

    /** @param array<string, mixed> $overrides */
    public function createEventFromTemplate(EventTemplate $template, Model $organizer, array $overrides = []): Event
    {
        return $this->templates->spawn($template, $organizer, $overrides);
    }

    // ===== Sponsors =====

    /** @param array<string, mixed> $sponsorData */
    public function purchaseSponsorship(SponsorTier $tier, Model $buyer, string $companyName, array $sponsorData = []): Sponsor
    {
        return $this->sponsors->purchaseSponsorship($tier, $buyer, $companyName, $sponsorData);
    }

    /** @param array<string, string> $assignmentData */
    public function issueCompTicket(Sponsor $sponsor, Model $holder, array $assignmentData = []): Ticket
    {
        return $this->sponsors->issueCompTicket($sponsor, $holder, $assignmentData);
    }

    // ===== Referrals =====

    public function attributeReferral(Order $order, string $code): void
    {
        $link = ReferralLink::query()->where('code', $code)->where('active', true)->first();
        if ($link === null) {
            return;
        }
        $order->forceFill(['referral_link_id' => $link->id])->save();
        $link->increment('uses_count');
        ReferralAttributionRecorded::dispatch($order, $link);
    }

    // ===== Announcements =====

    /** @param array<string, mixed> $audienceFilter */
    public function announce(
        Event $event,
        Model $author,
        string $subject,
        string $body,
        AnnouncementAudience $audience = AnnouncementAudience::All,
        array $audienceFilter = [],
        ?DateTimeInterface $scheduledFor = null,
    ): Announcement {
        $announcement = Announcement::create([
            'event_id' => $event->id,
            'author_id' => $author->getKey(),
            'subject' => $subject,
            'body' => $body,
            'audience' => $audience,
            'audience_filter' => $audienceFilter,
            'scheduled_for' => $scheduledFor,
        ]);

        if ($scheduledFor === null) {
            $this->announcements->dispatch($announcement);
        }

        return $announcement;
    }

    // ===== GDPR =====

    /** @return array<string, mixed> */
    public function exportPersonalData(Model $user): array
    {
        return $this->gdprExporter->export($user);
    }

    public function anonymizePersonalData(Model $user): void
    {
        $this->gdprAnonymizer->anonymize($user);
    }

    // ===== Payouts =====

    public function accruePayouts(Order $order): void
    {
        $this->payouts->accrueFor($order);
    }

    public function recordPayout(int $ledgerEntryId, string $reference): void
    {
        $entry = PayoutLedgerEntry::query()->findOrFail($ledgerEntryId);
        $entry->forceFill([
            'status' => PayoutStatus::PaidOut,
            'paid_out_at' => now(),
            'payout_reference' => $reference,
        ])->save();
    }

    public function reversePayout(int $ledgerEntryId, string $reason): void
    {
        $entry = PayoutLedgerEntry::query()->findOrFail($ledgerEntryId);
        $entry->forceFill([
            'status' => PayoutStatus::Reversed,
        ])->save();
        // Reason is captured by caller via audit log (not persisted on payout entry in v1).
        unset($reason);
    }
}

<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Kurt\Modules\Events\Attendance\Enums\AttendeeStatus;
use Kurt\Modules\Events\Attendance\Models\Attendee;
use Kurt\Modules\Events\Catalog\Enums\EventStatus;
use Kurt\Modules\Events\Catalog\Enums\EventVisibility;
use Kurt\Modules\Events\Catalog\Models\Event;
use Kurt\Modules\Events\Flow\Enums\SponsorStatus;
use Kurt\Modules\Events\Flow\Models\Sponsor;
use Kurt\Modules\Events\Flow\Models\SponsorTier;
use Kurt\Modules\Events\Ticketing\Enums\DiscountApplicationScope;
use Kurt\Modules\Events\Ticketing\Enums\DiscountKind;
use Kurt\Modules\Events\Ticketing\Enums\DiscountScope;
use Kurt\Modules\Events\Ticketing\Enums\TicketStatus;
use Kurt\Modules\Events\Ticketing\Enums\TicketTypeMode;
use Kurt\Modules\Events\Ticketing\Models\DiscountCode;
use Kurt\Modules\Events\Ticketing\Models\PriceTier;
use Kurt\Modules\Events\Ticketing\Models\ReferralLink;
use Kurt\Modules\Events\Ticketing\Models\Ticket;
use Kurt\Modules\Events\Ticketing\Models\TicketAddOn;
use Kurt\Modules\Events\Ticketing\Models\TicketType;

final class DemoCommand extends Command
{
    /** @var string */
    protected $signature = 'events:demo';

    /** @var string */
    protected $description = 'Seed a sample event, ticket types, price tier, add-on, referral, discount, sponsor and attendees.';

    public function handle(): int
    {
        DB::transaction(function (): void {
            $event = Event::create([
                'slug' => 'events-v1-demo-'.time(),
                'title' => ['en' => 'Events v1 Demo'],
                'description' => ['en' => 'A seeded demo event for local development.'],
                'status' => EventStatus::Published,
                'visibility' => EventVisibility::Public,
                'starts_at' => now()->addDays(14),
                'ends_at' => now()->addDays(14)->addHours(4),
                'timezone' => 'UTC',
                'location_name' => 'Demo Venue',
                'capacity' => 250,
                'sale_starts_at' => now(),
                'sale_ends_at' => now()->addDays(13),
            ]);

            $type = TicketType::create([
                'event_id' => $event->id,
                'slug' => 'demo-general',
                'name' => ['en' => 'General Admission'],
                'mode' => TicketTypeMode::Open,
                'price_minor' => 2500,
                'currency' => 'USD',
                'refundable' => true,
                'transferable' => true,
                'consumer_protection_exempt' => false,
                'max_per_order' => 5,
                'capacity' => 200,
                'position' => 0,
            ]);

            PriceTier::create([
                'ticket_type_id' => $type->id,
                'name' => 'Early Bird',
                'price_minor' => 2000,
                'starts_at' => now(),
                'ends_at' => now()->addDays(7),
                'position' => 0,
            ]);

            TicketAddOn::create([
                'event_id' => $event->id,
                'slug' => 'demo-tshirt',
                'name' => ['en' => 'Demo T-shirt'],
                'price_minor' => 1500,
                'currency' => 'USD',
                'active' => true,
                'scannable' => false,
                'position' => 0,
            ]);

            ReferralLink::create([
                'event_id' => $event->id,
                'organizer_id' => 1,
                'code' => 'DEMOREF',
                'commission_basis_points' => 500,
                'active' => true,
            ]);

            DiscountCode::create([
                'code' => 'DEMO10',
                'description' => 'Demo 10% off',
                'kind' => DiscountKind::Percent,
                'amount_minor' => 1000,
                'application_scope' => DiscountApplicationScope::Order,
                'applies_to' => DiscountScope::Global,
                'active' => true,
            ]);

            $tier = SponsorTier::create([
                'event_id' => $event->id,
                'slug' => 'demo-gold',
                'name' => 'Gold',
                'price_minor' => 100000,
                'currency' => 'USD',
                'comp_ticket_quota' => 2,
                'comp_ticket_type_id' => $type->id,
                'position' => 0,
            ]);

            Sponsor::create([
                'event_id' => $event->id,
                'sponsor_tier_id' => $tier->id,
                'name' => 'Acme Demo Co.',
                'contact_user_id' => 1,
                'status' => SponsorStatus::Active,
                'position' => 0,
            ]);

            foreach (range(1, 3) as $i) {
                $ticket = Ticket::create([
                    'order_item_id' => 0,
                    'ticket_type_id' => $type->id,
                    'event_id' => $event->id,
                    'holder_name' => "Demo Holder {$i}",
                    'holder_email' => "demo{$i}@example.com",
                    'status' => TicketStatus::Issued,
                    'qr_token' => 'demo-token-'.$i.'-'.bin2hex(random_bytes(4)),
                ]);
                Attendee::create([
                    'event_id' => $event->id,
                    'ticket_id' => $ticket->id,
                    'user_id' => $i + 100,
                    'status' => AttendeeStatus::Registered,
                    'profile' => ['demo' => true],
                ]);
            }

            $this->info("Seeded demo event {$event->slug} (id: {$event->id}).");
        });

        return self::SUCCESS;
    }
}

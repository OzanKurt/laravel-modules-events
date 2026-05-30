# laravel-modules-events

Payment-agnostic event management module for Laravel: events, tickets, applications, sale queue, waitlist, refunds, transfers, dynamic requirements, sponsors, announcements, audit log, templates, GDPR helpers.

## Requirements

- PHP 8.4+
- Laravel 12.x
- `ozankurt/laravel-modules-core` v2.x

## Installation

```bash
composer require ozankurt/laravel-modules-events
```

Publish + run migrations:

```bash
php artisan vendor:publish --tag=events-config
php artisan vendor:publish --tag=events-migrations
php artisan migrate
```

## What it provides

- **Catalog** — Events, sessions, categories, tags, organizers (with revenue split), templates, ICS export, recurrence expansion.
- **Ticketing** — Ticket types (open/application/RSVP), price tiers (early bird), discount codes (percent or flat amount, per-order or per-ticket), add-ons (parking/dinner/merch), tickets with HMAC-signed QR tokens, group ticket assignment at checkout, transfers (with optional fees), referral attribution.
- **Attendance** — Attendees, applications with organizer approval, custom attendance forms, bulk announcements with audience filters, per-session check-in.
- **Eligibility** — Dynamic requirement engine with shipped evaluators (age min/max, document, group membership, gender, free-form, custom rule via FQCN) and document verification workflow.
- **Flow** — Sale queue (waiting-room), waitlist, refunds with EU consumer-protection window + buyer self-cancel, sponsor tier purchases with comp tickets, co-organizer payout ledger, audit log, GDPR export + anonymize + retention.
- **Top-level facade** — `Kurt\Modules\Events\Support\Events` (also available as `Kurt\Modules\Events\Facades\Events`).
- **Optional Laravel Notifications** — Mail + Database channels with publishable Blade templates.

## Filament admin

As of **v1.1**, the package ships Filament admin resources for Filament **v3, v4, and v5**.
Register the version-dispatching plugin on your panel — it resolves the correct
resource set from the installed Filament major automatically:

```php
use Filament\Panel;
use Kurt\Modules\Events\Filament\EventsPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        // ...
        ->plugin(EventsPlugin::make());
}
```

Eight resources are registered under an **Events** navigation group:

| Resource | Purpose |
|---|---|
| `EventResource` | Events — translatable title/description, status & visibility, schedule, category, capacity. Full CRUD. |
| `TicketTypeResource` | Ticket types — translatable name, mode (open/application/RSVP), price (minor units), capacity, sold count. Full CRUD. |
| `OrderResource` | Orders — read-mostly; status/buyer/total, "Request refund" row action. |
| `ApplicationResource` | Application review queue — approve / reject (with reason) row actions, defaults to pending. |
| `DiscountCodeResource` | Discount codes — kind (percent/flat), scope, amount, usage limits, validity window. Full CRUD. |
| `DocumentVerificationResource` | Document review queue — verify / reject row actions, defaults to pending. |
| `RefundResource` | Refunds — read-mostly; mark processed / mark failed row actions, defaults to pending. |
| `WaitlistResource` | Waitlist — read-only diagnostic table. |

Row actions on the read-mostly/queue resources delegate to the
`Kurt\Modules\Events\Support\Events` facade (`approve`, `reject`,
`requestRefund`, `markRefundProcessed`, `markRefundFailed`), so admin decisions
fire the same domain events your application already listens to.

Translatable fields render as per-locale tabs (`en`, `tr` by default) — no extra
Filament plugin dependency required.

## License

MIT (c) Ozan Kurt

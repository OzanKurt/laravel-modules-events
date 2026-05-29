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

Filament v3/v4/v5 admin resources land in v1.1. v1.0 is headless.

## License

MIT (c) Ozan Kurt

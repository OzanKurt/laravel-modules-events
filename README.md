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

## API

The package ships an out-of-the-box REST API built on the Core **API kit**
(`ozankurt/laravel-modules-core` ^2.2). It is **safe by default**: in the
default `headless` mode nothing is registered. Opt in per environment:

```dotenv
EVENTS_HTTP_MODE=api   # headless (default) | api | ui
```

Reads are public (scoped to published, public events); writes require auth and
a Policy. The whole surface is namespaced under the `events.http.prefix`
(default `api/events`) and throttled by the `events-api` limiter.

### Configuration

`config/events.php` exposes an `http` block (see the Core API kit for the full
convention):

```php
'http' => [
    'mode' => env('EVENTS_HTTP_MODE', 'headless'), // headless | api | ui
    'prefix' => 'api/events',
    'middleware' => ['api'],
    'auth_middleware' => ['auth'],  // appended to every write route
    'rate_limit' => '60,1',         // maxAttempts,decayMinutes
],
```

Point `auth_middleware` at whichever guard your app uses (e.g.
`['auth:sanctum']`). Every response uses the Core envelope: `{ "data": … }` on
success (with `meta.pagination` on lists) and `{ "message": …, "errors": … }`
on failure.

### Endpoints

All paths are relative to the `api/events` prefix. Reads are public; writes
require auth **and** pass the noted Policy check.

| Method | Path | Action | Auth / Policy |
|---|---|---|---|
| GET | `/` | List published, public events (sort `starts_at`/`created_at`, filter `category_id`/`timezone`, paginate) | public |
| GET | `/{event}` | Show an event | public (`EventPolicy::view`) |
| POST | `/` | Create an event (caller becomes owner) | auth |
| PATCH | `/{event}` | Update an event | `EventPolicy::update` |
| DELETE | `/{event}` | Delete an event | `EventPolicy::delete` |
| POST | `/{event}/publish` | Publish an event | `EventPolicy::update` |
| POST | `/{event}/cancel` | Cancel an event (`reason`) | `EventPolicy::update` |
| GET | `/{event}/attendees` | Organizer attendee roster | `EventPolicy::viewAttendees` |
| GET | `/{event}/ticket-types` | List an event's ticket types | public (`EventPolicy::view`) |
| POST | `/{event}/ticket-types` | Create a ticket type | `TicketTypePolicy::create` |
| PATCH | `/ticket-types/{ticketType}` | Update a ticket type | `TicketTypePolicy::update` |
| DELETE | `/ticket-types/{ticketType}` | Delete a ticket type | `TicketTypePolicy::delete` |
| GET | `/registrations` | The authenticated user's registrations (tickets held) | auth |
| POST | `/ticket-types/{ticketType}/register` | Register for a ticket type | auth (`EventPolicy::view`) |
| POST | `/orders/{order}/cancel` | Buyer self-cancel of a paid order | `OrderPolicy::view` |
| POST | `/tickets/{ticket}/check-in` | Check a ticket in at the door | `EventPolicy::checkIn` (owner/manager/scanner) |

### Registration, cancel and check-in enforce the same limits

The `register`, `cancel` and `check-in` endpoints are **thin adapters** over the
`Kurt\Modules\Events\Support\Events` domain service — they never reimplement or
bypass its rules. Every guard that runs when you call the service directly runs
identically through the API:

- **register** goes through `reserve()`, so sale-queue admission, ticket-type
  capacity and price-tier caps are all enforced. A sold-out ticket type returns
  a clean `409` (the capacity limiter held; nothing is oversold), not a bypass.
  A free registration is finalised immediately so a ticket is issued; paid
  orders stay `Pending` for your own payment flow (the module is
  payment-agnostic).
- **cancel** goes through `cancelOrderByBuyer()`, so the refund/cancellation
  window (EU consumer-protection window and/or per-ticket self-cancel deadline)
  and the post-check-in guard apply. Outside the window returns `422`.
- **check-in** goes through `checkIn()`, so the domain's replay protection
  applies — a ticket already checked in (or otherwise not issuable) returns
  `409`.

## License

MIT (c) Ozan Kurt

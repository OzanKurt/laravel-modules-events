# Changelog

All notable changes follow [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and [SemVer](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.0.0] - 2026-05-29

### Added
- Full v1 release of `ozankurt/laravel-modules-events`.
- 40 migrations across 5 sub-aggregates (Catalog, Ticketing, Attendance, Eligibility, Flow).
- Models: Event (+ recurrence + occurrences), EventCategory, EventTag, EventOrganizer, EventTemplate, Session, TicketType, PriceTier, Order, OrderItem, OrderItemAssignment, Ticket, TicketAddOn, TicketAddOnPurchase, ReferralLink, DiscountCode + Usage, Attendee, Application, AttendanceForm + Response, Announcement + AnnouncementRecipient, Requirement + RequirementCheck, DocumentUpload + DocumentVerification, SaleQueueEntry, WaitlistEntry, Refund, Sponsor + SponsorTier + SponsorCompTicket, PayoutLedgerEntry, CheckInAttempt, AuditLogEntry, SessionCheckIn.
- Open / Application / RSVP ticket types with hybrid per-type mode.
- Group ticket assignment at checkout (one buyer, multiple holders per seat).
- Multi-session events with per-session check-in.
- Sale queue (waiting room) — transport-agnostic (polling or broadcasting via `QueueReleased` `ShouldBroadcast` event).
- Waitlist with promotion engine + claim window.
- Refunds: full Refund model with EU consumer-protection window + per-type buyer self-cancel deadline + payment-gateway hook (`RefundRequested` event -> `markRefundProcessed/Failed`).
- Ticket transfers (free + with fee Order + deadline enforcement).
- Discount codes: percent (basis points) + flat amount with order or per-ticket scope; global or events-subset; usage limits + audit.
- Time-based pricing tiers per ticket type (early bird / regular / last minute).
- Pay-what-you-can ticket types via `minimum_price_minor`.
- Add-ons (parking/dinner/merch) with own scan tokens.
- Referral / affiliate attribution + lightweight co-organizer payout ledger.
- Bulk announcements with audience filters (all/registered/checked_in/by_ticket_type/by_session).
- Dynamic requirement engine: AgeMin/AgeMax/Document/GroupMembership/Gender/FreeForm/CustomRule evaluators + manual document verification + pluggable `DocumentVerifier` contract.
- Sponsor model: tiers with comp tickets + B2B billing order.
- Sponsor + organizer audit log (`events_audit_log`) — no spatie/activitylog dependency.
- Event templates: explicit `EventTemplate` model + `cloneEvent()` helper.
- ICS calendar export + recurrence expansion command.
- GDPR helpers: `exportPersonalData(User)`, `anonymizePersonalData(User)`, `events:enforce-retention` command, DPIA docs.
- Pluggable contracts: `QueueChallengeProvider`, `EventChatBridge`, `DocumentVerifier`, `RequirementEvaluator`, `GroupResolver`.
- Optional Laravel Notifications (Mail + Database) with publishable Blade templates.
- Console commands: `events:release-queue`, `events:prune-queue`, `events:expire-waitlist-claims`, `events:expire-pending-orders`, `events:dispatch-announcements`, `events:dispatch-reminders`, `events:generate-occurrences`, `events:enforce-retention`, `events:demo`.
- Domain events (~50) across all sub-aggregates.
- Policies for Event / TicketType / Order / Application / Refund.
- HMAC-signed QR check-in tokens with replay protection.
- Pessimistic locking on `ticket_types` row during `reserve()` to prevent oversubscribe.
- Cart abandonment auto-cancellation via `events:expire-pending-orders`.
- Search scopes on Event: published, upcoming, past, inCategory, withTags, organizedBy, nearLocation (Haversine).
- Attendee list 4-tier privacy (private / organizer_only / attendees_only / public).
- Optional platform-admin approval workflow (`EventStatus::PendingApproval`).
- GitHub Actions matrix CI (Laravel 12, PHP 8.4).

### Planned (v1.1)
- Filament v3/v4/v5 admin resources.
- Laravel Scout integration for full-text search.

### Inspirations
- Spec scope informed by Eventbrite-style flows + EU CRD Article 16(l) requirements.

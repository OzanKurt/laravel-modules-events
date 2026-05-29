# Personal Data Flow

This document describes how personal data flows through `ozankurt/laravel-modules-events`.

## Inputs

- Buyer details captured at order creation: `Order.user_id`, `OrderItemAssignment.holder_name`, `holder_email`, `holder_metadata`.
- Attendee profile (date_of_birth, gender, dietary, etc.) on `Attendee.profile` (JSON).
- Application form answers on `Application.metadata.form_answers`.
- Document uploads (`DocumentUpload` + Spatie medialibrary on configured disk).
- Audit log actor (`AuditLogEntry.actor_id` + `context.ip` + `user_agent`).
- Sale queue + waitlist join records (`SaleQueueEntry`, `WaitlistEntry`).

## Storage

All rows persist in the consumer's database. Document files live on the disk configured by `events.documents.disk` (default `private`).

## Outputs

- Domain events dispatched throughout the lifecycle (see `src/{Catalog,Ticketing,Attendance,Eligibility,Flow}/Events/`).
- Optional Laravel Notifications via mail + database channels.
- Broadcast events `QueueReleased` and `WaitlistPromoted` over the consumer's broadcast connection.

## Subject rights

`Events::exportPersonalData(User)` returns a structured dump across tables.
`Events::anonymizePersonalData(User)` replaces PII with deterministic hashes.
`events:enforce-retention` runs daily and anonymises stale records per `events.gdpr.retention_days`.

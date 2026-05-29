# Article 30 Processing Record (template)

Adapt this template to your deployment.

## Controller
- Name:
- Address:
- DPO contact:

## Categories of data subjects
- Event buyers, attendees, organizers, sponsors.

## Categories of personal data
- Identifiers: name, email, user id.
- Special categories: date of birth, gender (per `Attendee.profile`).
- Documents: uploaded ID / proof files (configurable).

## Purposes of processing
- Ticket issuance, attendance management, refund processing, communications.
- Eligibility verification.
- Audit trail for accountability.

## Retention
- Configured via `events.gdpr.retention_days`. Null disables automatic deletion.

## Recipients
- Consumer-owned payment processor (via `RefundRequested` event handler).
- Email / database notification channels.

## International transfers
- None within the module. Consumer broadcasts (e.g. Pusher) may transit data outside the EEA.

## Security measures
- HMAC-signed QR tokens.
- Pessimistic locking on capacity decrement.
- Audit log of state-changing actions.

<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;

it('creates every events_* table', function () {
    foreach ([
        'events_categories', 'events_tags', 'events_events', 'events_event_tag',
        'events_event_organizers', 'events_event_templates', 'events_sessions',
        'events_attendance_forms', 'events_ticket_types', 'events_ticket_type_session',
        'events_price_tiers', 'events_ticket_add_ons', 'events_sponsor_tiers',
        'events_referral_links', 'events_discount_codes', 'events_discount_code_event',
        'events_orders', 'events_order_items', 'events_order_item_assignments',
        'events_tickets', 'events_session_check_ins', 'events_ticket_add_on_purchases',
        'events_applications', 'events_attendees', 'events_attendance_responses',
        'events_announcements', 'events_announcement_recipients', 'events_requirements',
        'events_requirement_checks', 'events_document_uploads', 'events_document_verifications',
        'events_discount_code_usages', 'events_sponsors', 'events_sponsor_comp_tickets',
        'events_sale_queue_entries', 'events_waitlist_entries', 'events_refunds',
        'events_payout_ledger', 'events_check_in_attempts', 'events_audit_log',
    ] as $table) {
        expect(Schema::hasTable($table))->toBeTrue("missing {$table}");
    }
});

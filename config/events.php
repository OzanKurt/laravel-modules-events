<?php

declare(strict_types=1);
use Kurt\Modules\Events\Eligibility\Evaluators\AgeMaxEvaluator;
use Kurt\Modules\Events\Eligibility\Evaluators\AgeMinEvaluator;
use Kurt\Modules\Events\Eligibility\Evaluators\CustomRuleEvaluator;
use Kurt\Modules\Events\Eligibility\Evaluators\DocumentEvaluator;
use Kurt\Modules\Events\Eligibility\Evaluators\FreeFormEvaluator;
use Kurt\Modules\Events\Eligibility\Evaluators\GenderEvaluator;
use Kurt\Modules\Events\Eligibility\Evaluators\GroupMembershipEvaluator;

return [
    'currency' => env('EVENTS_DEFAULT_CURRENCY', 'USD'),

    // Out-of-the-box REST API, built on the Core API kit. Safe-by-default:
    // in `headless` mode nothing is registered. Set EVENTS_HTTP_MODE=api
    // (or `ui`) to expose the JSON endpoints in routes/api.php.
    'http' => [
        'mode' => env('EVENTS_HTTP_MODE', 'headless'),
        'prefix' => 'api/events',
        'middleware' => ['api'],
        'auth_middleware' => ['auth'],
        'rate_limit' => '60,1',
    ],

    'queue' => [
        'enabled' => true,
        'active_concurrency' => 100,
        'active_window_seconds' => 300,
        'heartbeat_timeout_seconds' => 60,
    ],

    'waitlist' => [
        'enabled' => true,
        'claim_window_seconds' => 600,
    ],

    'recurrence' => [
        'enabled' => true,
        'window_days' => 90,
    ],

    'refunds' => [
        'consumer_protection_window_days' => 14,
    ],

    'transfers' => [
        'allowed_by_default' => true,
    ],

    'tax' => [
        'enabled' => true,
    ],

    'publishing' => [
        'require_approval' => false,
    ],

    'audit' => [
        'enabled' => true,
        'capture_context' => true,
    ],

    'anti_bot' => [
        'queue_challenge' => null,
    ],

    'chat_bridge' => [
        'provider' => null,
    ],

    'gdpr' => [
        'retention_days' => null,
        'anonymize_audit_log_actor' => true,
    ],

    'payouts' => [
        'auto_accrue_on_order_paid' => true,
    ],

    'documents' => [
        'disk' => env('EVENTS_DOCUMENT_DISK', 'private'),
        'verifier' => null,
    ],

    'requirements' => [
        'evaluators' => [
            'age_min' => AgeMinEvaluator::class,
            'age_max' => AgeMaxEvaluator::class,
            'document' => DocumentEvaluator::class,
            'group_membership' => GroupMembershipEvaluator::class,
            'gender' => GenderEvaluator::class,
            'free_form_question' => FreeFormEvaluator::class,
            'custom_rule' => CustomRuleEvaluator::class,
        ],
        'group_resolver' => null,
    ],

    'notifications' => [
        'enabled' => false,
        'channels' => ['mail', 'database'],
    ],

    'broadcasting' => [
        'enabled' => true,
    ],

    'reminders' => [
        'enabled' => true,
        'before_hours' => [24, 1],
    ],

    'orders' => [
        'pending_timeout_minutes' => 15,
    ],

    'check_in' => [
        'token_lifetime_minutes' => 0,
        'replay_protection' => true,
    ],

    'search' => [
        'geo' => [
            'enabled' => false,
            'distance_unit' => 'km',
        ],
    ],

    'models' => [
        // overrides for downstream model swaps
    ],
];

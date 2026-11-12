<?php

return [
    'monitor_enabled' => (bool) env('SECURITY_MONITOR_ENABLED', false),
    'failure_alert_threshold' => max(5, (int) env('SECURITY_FAILURE_ALERT_THRESHOLD', 20)),
    'challenge_minutes' => 5,
    'reauth_minutes' => 5,
    'event_retention_days' => min(90, max(1, (int) env('SECURITY_EVENT_RETENTION_DAYS', 30))),
    // Real mail requires explicit operator configuration, never a log transport.
    'recovery_enabled' => (bool) env('SECURITY_RECOVERY_ENABLED', false),
    'frontend_url' => env('SECURITY_FRONTEND_URL', env('APP_URL', 'http://localhost:5173')),
    'scan_uploads' => (bool) env('SECURITY_SCAN_UPLOADS', false),
    'clamav_host' => env('CLAMAV_HOST', '127.0.0.1'),
    'clamav_port' => (int) env('CLAMAV_PORT', 3310),
    'scanner_timeout' => min(10, max(1, (int) env('CLAMAV_TIMEOUT', 3))),
];

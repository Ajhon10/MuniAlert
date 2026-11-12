<?php

return [
    'persistent_login' => (bool) env('AUTH_PERSISTENT_LOGIN', true),
    'scheduled_push' => (bool) env('HAZARD_SCHEDULED_PUSH', false),
    'session_days' => max(1, (int) env('AUTH_SESSION_DAYS', 90)),
    // The legacy public-photo setting cannot make new evidence public.
    'photo_disk' => env('HAZARD_EVIDENCE_DISK', 'evidence'),
    'location_review_meters' => 250,
    'fcm_project_id' => env('FCM_PROJECT_ID'),
    'fcm_credentials' => env('GOOGLE_APPLICATION_CREDENTIALS'),
    'demo_admin_password' => env('HAZARD_DEMO_ADMIN_PASSWORD'),
    'demo_admin_email' => env('HAZARD_DEMO_ADMIN_EMAIL', 'admin@example.test'),
    'demo_citizen_one_password' => env('HAZARD_DEMO_CITIZEN_ONE_PASSWORD'),
    'demo_citizen_two_password' => env('HAZARD_DEMO_CITIZEN_TWO_PASSWORD'),
];

<?php

return [
    'enabled' => (bool) env('IMAGE_DETECTOR_ENABLED', false),
    'endpoint' => env('IMAGE_DETECTOR_URL', 'http://127.0.0.1:8099/classify'),
    'token' => env('IMAGE_DETECTOR_TOKEN'),
];

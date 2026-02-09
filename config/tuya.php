<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Tuya API Configuration
    |--------------------------------------------------------------------------
    */

    'region' => env('TUYA_REGION', 'EU'),

    'base_url' => env('TUYA_BASE_URL', 'https://openapi.tuyaeu.com'),

    // PIN requirements for Smart Lock X7
    'pin_length' => 7, // Must be exactly 7 digits for X7 model
    'pin_min_duration' => 60, // Minimum duration in seconds (1 minute)
    'pin_max_duration' => 86400 * 30, // Maximum duration (30 days)

    // Sync intervals
    'sync_logs_interval' => env('TUYA_SYNC_LOGS_INTERVAL', 15), // minutes
    'sync_alerts_interval' => env('TUYA_SYNC_ALERTS_INTERVAL', 10), // minutes

    // API timeouts
    'timeout' => 30, // seconds
    'retry_attempts' => 3,
    'retry_delay' => 1000, // milliseconds

    // Token cache
    'token_cache_key' => 'tuya_access_token',
    'token_refresh_buffer' => 300, // Refresh 5 minutes before expiry

];

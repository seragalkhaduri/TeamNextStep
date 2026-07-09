<?php

/**
 * UIMP-specific configuration values.
 *
 * Security parameters from SDD §6.2.
 * All values are configurable via .env to support different environments.
 */
return [

    /*
    |--------------------------------------------------------------------------
    | Account Lockout (SDD §6.2)
    |--------------------------------------------------------------------------
    */
    'lockout' => [
        'attempts' => (int) env('UIMP_LOCKOUT_ATTEMPTS', 5),
        'window_minutes' => (int) env('UIMP_LOCKOUT_WINDOW_MINUTES', 15),
        'duration_minutes' => (int) env('UIMP_LOCKOUT_DURATION_MINUTES', 15),
    ],

    /*
    |--------------------------------------------------------------------------
    | Refresh Token (SDD §6.2)
    |--------------------------------------------------------------------------
    */
    'refresh_token_days' => (int) env('UIMP_REFRESH_TOKEN_DAYS', 7),

    /*
    |--------------------------------------------------------------------------
    | Password Reset (SDD §6.2)
    |--------------------------------------------------------------------------
    */
    'password_reset_expiry_minutes' => (int) env('UIMP_PASSWORD_RESET_EXPIRY_MINUTES', 60),

    /*
    |--------------------------------------------------------------------------
    | Data Retention (SDD §13, TBD-005)
    |--------------------------------------------------------------------------
    | Configurable values — pending governance approval.
    | No auto-hard-delete without manual confirmation step.
    */
    'retention' => [
        'soft_deleted_years' => 7,
        'audit_logs_hot_years' => 1,
        'audit_logs_archive_years' => 9,
        'sessions_minutes' => 30,
        'notifications_years' => 2,
    ],

];

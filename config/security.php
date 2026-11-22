<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Content Security Policy
    |--------------------------------------------------------------------------
    */

    'csp_enabled' => (bool) env('CSP_ENABLED', true),

    'csp_unsafe_eval' => (bool) env('CSP_UNSAFE_EVAL', false),

    /*
    |--------------------------------------------------------------------------
    | Security Logging
    |--------------------------------------------------------------------------
    */

    'log_security' => (bool) env('LOG_SECURITY', true),

    'log_failed_login' => (bool) env('LOG_FAILED_LOGIN', true),

];

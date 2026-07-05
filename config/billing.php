<?php

return [

    'provider' => 'bank_transfer',

    'complimentary_provider' => 'complimentary',

    'bank_name' => env('BILLING_BANK_NAME', 'Bank of Palestine'),

    'account_holder' => env('BILLING_ACCOUNT_HOLDER', env('LEGAL_OPERATOR_NAME', 'Yousif ElFarra')),

    'iban' => env('BILLING_IBAN'),

    'swift' => env('BILLING_SWIFT', 'PALSPS22XXX'),

    'currency' => env('BILLING_CURRENCY', 'USD'),

    'extra_instructions' => env('BILLING_EXTRA_INSTRUCTIONS', ''),

    /*
    |--------------------------------------------------------------------------
    | Paid plan grace period (bank transfer)
    |--------------------------------------------------------------------------
    |
    | After subscription_renews_at passes, status becomes "past_due". The user
    | keeps access for this many days before being downgraded to Free.
    |
    */
    'grace_period_days' => (int) env('BILLING_GRACE_PERIOD_DAYS', 7),

    /*
    |--------------------------------------------------------------------------
    | Pricing presentation
    |--------------------------------------------------------------------------
    */
    'recommended_plan_slug' => env('BILLING_RECOMMENDED_PLAN_SLUG', 'pro'),

    'activation_hours' => (int) env('BILLING_ACTIVATION_HOURS', 24),

];

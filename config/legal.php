<?php

return [

  /*
  |--------------------------------------------------------------------------
  | Business & support (Paddle / compliance)
  |--------------------------------------------------------------------------
  */

  'operator_name' => env('LEGAL_OPERATOR_NAME', env('SEO_PARENT_NAME', 'Yousif ElFarra')),

  'business_name' => env('LEGAL_BUSINESS_NAME', env('SEO_SITE_NAME', env('APP_NAME', 'Downloader'))),

  'support_email' => env('LEGAL_SUPPORT_EMAIL', 'contact@yousiffarra.com'),

  'support_response_hours' => (int) env('LEGAL_SUPPORT_RESPONSE_HOURS', 48),

  'refund_window_days' => (int) env('LEGAL_REFUND_WINDOW_DAYS', 14),

  'last_updated' => env('LEGAL_LAST_UPDATED', '2026-07-02'),

];

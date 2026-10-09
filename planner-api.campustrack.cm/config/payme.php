<?php

return [

    /*
    |--------------------------------------------------------------------------
    | PayMe (MamoniPay)
    |--------------------------------------------------------------------------
    |
    | URLs et chemins d'endpoints. Les valeurs de production doivent être
    | confirmées (voir §10 du plan). La config par tenant (marchand) vit dans
    | `payment_gateways` (gérée par le super-admin), ces chemins sont fixes.
    |
    */

    'sandbox_base_url' => env('PAYME_SANDBOX_BASE_URL', 'https://sandbox.mamonipay.me/api'),

    'live_base_url' => env('PAYME_LIVE_BASE_URL', 'https://api.mamonipay.me/api'),

    'login_path' => env('PAYME_LOGIN_PATH', '/auth/login'),

    'init_payment_path' => env('PAYME_INIT_PAYMENT_PATH', '/transaction/init_payment'),

    'payment_status_path' => env('PAYME_PAYMENT_STATUS_PATH', '/transaction/payment_status'),

    'payment_list_path' => env('PAYME_PAYMENT_LIST_PATH', '/transaction/payment_list'),

    'timeout' => (int) env('PAYME_TIMEOUT', 15),

    'token_ttl' => (int) env('PAYME_TOKEN_TTL', 50), // minutes

];

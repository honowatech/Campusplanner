<?php

return [

    /*
    |--------------------------------------------------------------------------
    | PayMe (MamoniPay)
    |--------------------------------------------------------------------------
    |
    | URL de base et chemins d'endpoints, conformes à la documentation
    | MAMONIPAY_API.md. Les chemins incluent le préfixe `/api`.
    |
    | La configuration par tenant (marchand) vit dans `payment_gateways`
    | (gérée par le super-admin) : `user_name`, `password` (chiffré) et
    | l'éventuelle surcharge `endpoint` (base URL sandbox/live).
    |
    */

    'sandbox_base_url' => env('PAYME_SANDBOX_BASE_URL', 'https://sandbox.mamonipay.me'),

    'live_base_url' => env('PAYME_LIVE_BASE_URL', 'https://mamonipay.me'),

    'login_path' => env('PAYME_LOGIN_PATH', '/api/auth/login'),

    'init_payment_path' => env('PAYME_INIT_PAYMENT_PATH', '/api/transaction/init_payment'),

    'payment_status_path' => env('PAYME_PAYMENT_STATUS_PATH', '/api/clients/transaction/payment_status'),

    'timeout' => (int) env('PAYME_TIMEOUT', 15),

    'token_ttl' => (int) env('PAYME_TOKEN_TTL', 50), // minutes

    // Durée maximale du polling d'un paiement (minutes). Au-delà, le paiement
    // est marqué « à vérifier manuellement » (cf. MAMONIPAY_API.md).
    'polling_timeout' => (int) env('PAYME_POLLING_TIMEOUT', 15),

];

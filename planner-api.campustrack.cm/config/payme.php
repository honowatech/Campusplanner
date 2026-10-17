<?php

return [

    /*
    |--------------------------------------------------------------------------
    | PayMe (MamoniPay) — API Partenaire
    |--------------------------------------------------------------------------
    |
    | Contrat réel (cf. docs/FEUILLE_DE_ROUTE_PAYME.md § 0) : adresse de base
    | unique, sans sandbox, sans `/v1` final, jamais l'adresse `/docs`.
    |
    | Les identifiants du compte marchand vivent dans `payment_gateways`
    | (gérés par le super-admin) : `user_name`, `password` (chiffré) et
    | l'éventuelle surcharge `endpoint` (adresse de base de l'API).
    |
    */

    'base_url' => env('PAYME_BASE_URL', 'https://console-v2.mamonipay.me/api'),

    'timeout' => (int) env('PAYME_TIMEOUT', 20),

    'endpoints' => [
        'login' => '/v1/auth/login',
        'init' => '/v1/payments/init',
        'verify' => '/v1/payments/verify/{reference}',
    ],

    // Délai (minutes) avant d'abandonner une demande sans confirmation (PayMe
    // ne signale pas l'expiration). Surchargé par `payment_gateways.timeout_minutes`.
    'timeout_minutes' => (int) env('PAYME_TIMEOUT_MINUTES', 15),

    // Durée (minutes) pendant laquelle une demande expirée ou annulée reste
    // revérifiée : le client peut valider tard sur son téléphone.
    'recovery_minutes' => (int) env('PAYME_RECOVERY_MINUTES', 60),

];

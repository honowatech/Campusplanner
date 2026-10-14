<?php

return [

    /*
    |--------------------------------------------------------------------------
    | PayMe (MamoniPay) — API Partenaire
    |--------------------------------------------------------------------------
    |
    | Contrat réel (cf. docs/FEUILLE_DE_ROUTE_PAYME.md §0) :
    |   - une seule adresse de base, pas de sandbox, pas de `/v1` à la fin,
    |     jamais l'adresse de la documentation (`/docs`) ;
    |   - connexion `POST /v1/auth/login` { login, password } → data.access_token ;
    |   - initiation `POST /v1/payments/init` ;
    |   - vérification `POST /v1/payments/verify/{gateway_reference}`.
    |
    | Les identifiants du marchand (user_name, password chiffré) et la
    | surcharge éventuelle de l'adresse de base vivent dans `payment_gateways`.
    |
    */

    'base_url' => env('PAYME_BASE_URL', 'https://console-v2.mamonipay.me/api'),

    // Délai HTTP (secondes) par requête.
    'timeout' => (int) env('PAYME_TIMEOUT', 20),

    'endpoints' => [
        'login' => '/v1/auth/login',
        'init' => '/v1/payments/init',
        'verify' => '/v1/payments/verify/{reference}',
    ],

    // Délai (minutes) avant d'abandonner une demande sans confirmation :
    // PayMe ne signale pas l'expiration. Surchargé par
    // `payment_gateways.timeout_minutes` lorsqu'il est renseigné.
    'timeout_minutes' => (int) env('PAYME_TIMEOUT_MINUTES', 15),

    // Délai (minutes) au-delà duquel une demande jamais initiée chez PayMe
    // (aucune gateway_reference) est abandonnée.
    'uninitiated_timeout_minutes' => (int) env('PAYME_UNINITIATED_TIMEOUT_MINUTES', 2),

    // Durée (minutes) pendant laquelle une demande expirée ou annulée est
    // revérifiée : le client peut valider tard sur son téléphone.
    'recovery_minutes' => (int) env('PAYME_RECOVERY_MINUTES', 60),

];

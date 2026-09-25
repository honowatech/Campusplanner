<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Alert Thresholds
    |--------------------------------------------------------------------------
    |
    | Configuration des seuils pour les alertes du dashboard
    |
    */
    'alerts' => [
        'class_capacity_threshold' => 0.95,      // 95% = alerte critique
        'teacher_hours_threshold' => 0.90,       // 90% des heures max = alerte
        'room_utilization_threshold' => 0.85,    // 85% d'occupation = alerte
        'pending_blockings_days' => 3,           // Alertes après 3 jours
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration du cache Redis pour le dashboard
    |
    */
    'cache' => [
        'realtime_ttl' => 300,      // 5 minutes (temps réel)
        'heavy_ttl' => 3600,        // 1 heure (stats lourdes)
        'prefix' => 'dashboard:',
        'driver' => 'database',
    ],

    /*
    |--------------------------------------------------------------------------
    | Pagination
    |--------------------------------------------------------------------------
    |
    | Configuration de la pagination pour les listes
    |
    */
    'pagination' => [
        'activity_per_page' => 10,
        'alerts_per_page' => 10,
    ],

    /*
    |--------------------------------------------------------------------------
    | Date Format
    |--------------------------------------------------------------------------
    |
    | Format de date ISO 8601
    |
    */
    'date_format' => 'Y-m-d\TH:i:s\Z',

    /*
    |--------------------------------------------------------------------------
    | Schedule Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration des fréquences de pré-calcul
    |
    */
    'schedule' => [
        'realtime_interval' => 'everyFiveMinutes',
        'heavy_interval' => 'hourly',
    ],
];

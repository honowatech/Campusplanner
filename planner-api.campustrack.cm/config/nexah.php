<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Nexah Bulk SMS
    |--------------------------------------------------------------------------
    |
    | Crédences par tenant (voir SmsCredential). L'URL de base et les chemins
    | d'endpoints sont configurables : la valeur exacte de production doit être
    | confirmée avec le fournisseur (voir §10 du plan d'implémentation).
    |
    */

    'base_url' => env('NEXAH_API_BASE_URL', 'https://sms.nexah.net/api'),

    'send_path' => env('NEXAH_SEND_PATH', '/sms/send'),

    'balance_path' => env('NEXAH_BALANCE_PATH', '/sms/balance'),

    'timeout' => (int) env('NEXAH_TIMEOUT', 15),

];

<?php

return [
    'sms' => [
        'settings_retrieved' => 'Configuration SMS récupérée',
        'settings_updated' => 'Configuration SMS mise à jour',
        'sent' => 'SMS envoyé',
        'test_sent' => 'SMS de test envoyé',
        'logs_retrieved' => 'Journal SMS récupéré',
    ],

    'demo' => [
        'status_retrieved' => 'État du mode démo récupéré',
        'enabled' => 'Mode démo activé',
        'disabled' => 'Mode démo désactivé',
        'disabled_error' => 'Le mode démo est désactivé.',
        'no_account' => 'Aucun compte démo disponible pour ce rôle.',
        'logged_in' => 'Connecté en mode démo',
        'no_tenant' => 'Aucun tenant démo.',
    ],

    'billing' => [
        'packs_retrieved' => 'Liste des packs récupérée',
        'subscription_retrieved' => 'Abonnement courant récupéré',
    ],

    'payments' => [
        'initiated' => 'Paiement initié',
        'history_retrieved' => 'Historique des paiements récupéré',
        'retrieved' => 'Paiements récupérés',
        'cancelled' => 'Demande de paiement annulée',
        'cannot_cancel' => 'Ce paiement ne peut plus être annulé.',
        'not_found' => 'Paiement introuvable.',
        'storage_error' => "Enregistrement du paiement impossible : la base de données n'est pas à jour (exécuter php artisan migrate).",
    ],

    'tenants' => [
        'list' => 'Liste des tenants récupérée',
        'created' => 'Tenant créé',
        'retrieved' => 'Tenant récupéré',
        'updated' => 'Tenant mis à jour',
        'suspended' => 'Tenant suspendu',
        'reactivated' => 'Tenant réactivé',
        'demo_not_deletable' => 'Le tenant démo ne peut pas être supprimé.',
        'deleted' => 'Tenant supprimé',
    ],

    'audit_logs' => [
        'retrieved' => "Journal d'audit récupéré",
    ],

    'features' => [
        'list' => 'Liste des fonctionnalités récupérée',
        'updated' => 'Fonctionnalité mise à jour',
    ],

    'packs' => [
        'list' => 'Liste des packs récupérée',
        'created' => 'Pack créé',
        'updated' => 'Pack mis à jour',
        'in_use' => 'Ce pack est utilisé par des abonnements.',
        'deleted' => 'Pack supprimé',
    ],

    'payment_gateway' => [
        'retrieved' => 'Passerelle récupérée',
        'updated' => 'Passerelle mise à jour',
        'invalid_endpoint' => "Indiquer l'adresse de base de l'API (ex. https://console-v2.mamonipay.me/api), jamais l'adresse de la documentation (/docs) ni une adresse se terminant par /v1.",
        'not_configured' => 'Identifiants PayMe non renseignés.',
        'test_ok' => 'Connexion PayMe réussie',
        'test_failed' => 'Connexion PayMe échouée',
    ],

    'onboarding' => [
        'registered' => 'Inscription réussie',
    ],

    'impersonation' => [
        'started' => 'Impersonation démarrée',
        'none_active' => 'Aucune impersonation active.',
        'admin_not_found' => 'Compte administrateur introuvable.',
        'stopped' => 'Impersonation terminée',
    ],

    'tenant' => [
        'not_found' => 'Aucun tenant associé à ce compte.',
    ],
];

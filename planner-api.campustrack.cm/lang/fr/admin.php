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

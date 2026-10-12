<?php

return [
    'sms' => [
        'settings_retrieved' => 'SMS configuration retrieved',
        'settings_updated' => 'SMS configuration updated',
        'sent' => 'SMS sent',
        'test_sent' => 'Test SMS sent',
        'logs_retrieved' => 'SMS log retrieved',
    ],

    'demo' => [
        'status_retrieved' => 'Demo mode status retrieved',
        'enabled' => 'Demo mode enabled',
        'disabled' => 'Demo mode disabled',
        'disabled_error' => 'Demo mode is disabled.',
        'no_account' => 'No demo account available for this role.',
        'logged_in' => 'Signed in as demo',
        'no_tenant' => 'No demo tenant.',
    ],

    'billing' => [
        'packs_retrieved' => 'Packs list retrieved',
        'subscription_retrieved' => 'Current subscription retrieved',
    ],

    'payments' => [
        'initiated' => 'Payment initiated',
        'history_retrieved' => 'Payment history retrieved',
        'retrieved' => 'Payments retrieved',
    ],

    'tenants' => [
        'list' => 'Tenants list retrieved',
        'created' => 'Tenant created',
        'retrieved' => 'Tenant retrieved',
        'updated' => 'Tenant updated',
        'suspended' => 'Tenant suspended',
        'reactivated' => 'Tenant reactivated',
        'demo_not_deletable' => 'The demo tenant cannot be deleted.',
        'deleted' => 'Tenant deleted',
    ],

    'audit_logs' => [
        'retrieved' => 'Audit log retrieved',
    ],

    'features' => [
        'list' => 'Features list retrieved',
        'updated' => 'Feature updated',
    ],

    'packs' => [
        'list' => 'Packs list retrieved',
        'created' => 'Pack created',
        'updated' => 'Pack updated',
        'in_use' => 'This pack is used by subscriptions.',
        'deleted' => 'Pack deleted',
    ],

    'payment_gateway' => [
        'retrieved' => 'Payment gateway retrieved',
        'updated' => 'Payment gateway updated',
    ],

    'onboarding' => [
        'registered' => 'Registration successful',
    ],

    'impersonation' => [
        'started' => 'Impersonation started',
        'none_active' => 'No impersonation active.',
        'admin_not_found' => 'Administrator account not found.',
        'stopped' => 'Impersonation stopped',
    ],

    'tenant' => [
        'not_found' => 'No tenant associated with this account.',
    ],
];

<?php

return [
    'auth' => [
        'registered' => 'Account created successfully. Pending administrator approval.',
        'account_pending' => 'Your account is pending administrator approval.',
        'signed_in' => 'User signed in successfully',
        'invalid_credentials' => 'Invalid credentials',
        'signed_out' => 'User signed out successfully',
        'not_authenticated' => 'User not authenticated',
        'authenticated' => 'User is authenticated',
    ],

    'password_reset' => [
        'link_sent' => 'If an account exists for this address, a reset link has just been sent to it.',
        'invalid_token' => 'This reset link is invalid or has expired. Request a new one.',
        'done' => 'Your password has been changed. You can now sign in.',
        'mail_subject' => 'Reset your :app password',
        'mail_greeting' => 'Hello :name,',
        'mail_intro' => 'You are receiving this email because a password reset was requested for your account.',
        'mail_action' => 'Choose a new password',
        'mail_expire' => 'This link expires in :count minutes.',
        'mail_ignore' => 'If you did not request it, ignore this email: your password stays unchanged.',
    ],

    'settings' => [
        'retrieved' => 'Settings retrieved',
        'updated' => 'Settings updated',
    ],

    'room_blockings' => [
        'list' => 'Room blockings list retrieved',
        'created' => 'Room blocking created successfully',
        'conflicts_warning' => 'This blocking conflicts with existing plannings',
        'retrieved' => 'Room blocking retrieved',
        'updated' => 'Room blocking updated successfully',
        'deleted' => 'Room blocking deleted successfully',
        'conflict_check' => 'Conflict check completed',
    ],

    'teacher_blockings' => [
        'list' => 'Teacher blockings list retrieved',
        'already_approved' => 'You already have an approved blocking for this period.',
        'submitted' => 'Blocking request submitted successfully and is pending approval',
        'retrieved' => 'Teacher blocking retrieved',
        'cannot_update' => 'Cannot update a blocking that has already been :status',
        'updated' => 'Blocking request updated successfully',
        'cannot_delete' => 'Cannot delete a blocking that has already been :status',
        'deleted' => 'Blocking request deleted successfully',
        'already_processed' => 'This blocking has already been :status',
        'approved' => 'Blocking request approved successfully',
        'rejected' => 'Blocking request rejected',
        'pending' => 'Pending blockings retrieved',
        'no_profile' => 'No teacher profile found for this user',
        'my_list' => 'My blockings retrieved',
        'availability_checked' => 'Teacher availability checked',
    ],

    'errors' => [
        'unauthenticated' => 'Unauthenticated.',
        'tenant_suspended' => 'Your account is suspended. Contact your administrator.',
        'demo_delete_disabled' => 'Deletions are disabled on the demo tenant.',
        'feature_not_included' => 'This feature is not included in your subscription plan.',
    ],
];

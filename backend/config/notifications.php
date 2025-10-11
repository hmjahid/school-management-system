<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Notification Settings
    |--------------------------------------------------------------------------
    |
    | This file contains the default notification settings for the application.
    | You can customize these settings based on your application's requirements.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Notification Types
    |--------------------------------------------------------------------------
    |
    | Define the available notification types and their default channels.
    | Each type can be delivered through one or more channels.
    | Available channels: database, mail, sms, push
    |
    */

    'types' => [
        // System Notifications
        'system.alert' => ['database', 'mail'],
        'system.maintenance' => ['database', 'mail', 'push'],
        'system.update' => ['database', 'mail'],
        
        // User Account Notifications
        'user.registered' => ['database', 'mail'],
        'user.verified' => ['database', 'mail'],
        'user.password_reset' => ['mail'],
        'user.password_updated' => ['mail'],
        'user.profile_updated' => ['database', 'mail'],
        
        // Course Notifications
        'course.enrolled' => ['database', 'mail'],
        'course.completed' => ['database', 'mail', 'push'],
        'course.reminder' => ['database', 'mail', 'push'],
        'course.certificate_available' => ['database', 'mail'],
        
        // Assignment Notifications
        'assignment.assigned' => ['database', 'mail'],
        'assignment.submitted' => ['database', 'mail'],
        'assignment.graded' => ['database', 'mail', 'push'],
        'assignment.reminder' => ['database', 'mail', 'push'],
        'assignment.overdue' => ['database', 'mail', 'sms'],
        
        // Exam Notifications
        'exam.scheduled' => ['database', 'mail'],
        'exam.reminder' => ['database', 'mail', 'push', 'sms'],
        'exam.result_available' => ['database', 'mail', 'push'],
        
        // Payment Notifications
        'payment.received' => ['database', 'mail'],
        'payment.failed' => ['database', 'mail', 'sms'],
        'payment.refunded' => ['database', 'mail'],
        'payment.reminder' => ['database', 'mail', 'sms'],
        
        // Refund Notifications
        'refund.requested' => ['database', 'mail'],
        'refund.approved' => ['database', 'mail', 'push'],
        'refund.rejected' => ['database', 'mail'],
        'refund.processed' => ['database', 'mail', 'push', 'sms'],
        
        // Support Notifications
        'support.ticket_created' => ['database', 'mail'],
        'support.ticket_updated' => ['database', 'mail'],
        'support.ticket_resolved' => ['database', 'mail', 'push'],
        'support.reply_received' => ['database', 'mail', 'push'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Channels
    |--------------------------------------------------------------------------
    |
    | Define the available notification channels and their configurations.
    |
    */

    'channels' => [
        'database' => [
            'enabled' => true,
            'via' => 'database',
            'icon' => 'fa-bell',
        ],
        'mail' => [
            'enabled' => true,
            'via' => 'mail',
            'icon' => 'fa-envelope',
            'from' => [
                'address' => env('MAIL_FROM_ADDRESS', 'notifications@schoolms.test'),
                'name' => env('MAIL_FROM_NAME', 'School Management System'),
            ],
        ],
        'sms' => [
            'enabled' => true,
            'via' => 'sms',
            'icon' => 'fa-sms',
            'from' => env('SMS_FROM', 'SCHOOL'),
        ],
        'push' => [
            'enabled' => true,
            'via' => 'push',
            'icon' => 'fa-mobile-alt',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Preferences
    |--------------------------------------------------------------------------
    |
    | Default notification preferences for users.
    |
    */

    'default_preferences' => [
        'allow_email' => true,
        'allow_sms' => false,
        'allow_push' => true,
        'mute_all' => false,
        'do_not_disturb' => [
            'enabled' => false,
            'start_time' => '22:00',
            'end_time' => '07:00',
            'days' => [0, 6], // Sunday, Saturday
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Templates
    |--------------------------------------------------------------------------
    |
    | Default templates for different notification types and channels.
    | These can be overridden in the database.
    |
    */

    'templates' => [
        'user.registered' => [
            'mail' => [
                'subject' => 'Welcome to {{app_name}}!',
                'content' => "Hello {{user_name}},\n\nThank you for registering with {{app_name}}. We're excited to have you on board!\n\nPlease verify your email address by clicking the button below.\n\n{{action_button|Verify Email|{{verification_url}}}}\n\nIf you did not create an account, no further action is required.\n\nThanks,\n{{app_name}} Team",
            ],
            'sms' => [
                'content' => "Welcome to {{app_name}}! Verify your email: {{verification_url}}",
            ],
            'push' => [
                'title' => 'Welcome to {{app_name}}!',
                'body' => 'Thank you for registering. Please verify your email address.',
            ],
        ],
        // Add more templates as needed
    ],
];

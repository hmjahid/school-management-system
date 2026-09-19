<?php

declare(strict_types=1);

/**
 * Dashboard authorization map (parity with the Laravel app's route
 * middleware groups, e.g. `role:admin`, `student_guardian`).
 *
 * Pure data/config — no BD/INT branching.
 */

return [

    /*
    | Roles allowed to use the /dashboard surface at all. Any authenticated
    | role outside this list (e.g. student, guardian) is denied.
    */
    'dashboard_roles' => ['super_admin', 'admin', 'teacher', 'accountant', 'librarian'],

    /*
    | Per-module role overrides. Controllers not listed here fall back to
    | `dashboard_roles`. Keyed by controller class suffix for readability.
    | Map any new Dashboard controller here so a default grant is deliberate.
    */
    'module_roles' => [

        // System / configuration — admins only.
        'UserController'              => ['super_admin', 'admin'],
        'RoleController'              => ['super_admin', 'admin'],
        'PermissionController'        => ['super_admin', 'admin'],
        'SettingController'           => ['super_admin', 'admin'],
        'BackupController'            => ['super_admin', 'admin'],
        'BulkController'              => ['super_admin', 'admin'],
        'OnboardingController'        => ['super_admin', 'admin'],
        'PaymentGatewayController'    => ['super_admin', 'admin'],
        'MediaController'             => ['super_admin', 'admin'],
        'DocumentController'          => ['super_admin', 'admin'],
        'CmsController'               => ['super_admin', 'admin'],
        'CommunicationController'     => ['super_admin', 'admin'],
        'VisitorLogController'        => ['super_admin', 'admin'],
        'ActivityController'          => ['super_admin', 'admin'],
        'SmsController'               => ['super_admin', 'admin'],
        'PayrollController'           => ['super_admin', 'admin'],
        'StaffAttendanceController'   => ['super_admin', 'admin'],

        // Finance — admins + accountants.
        'FeeController'               => ['super_admin', 'admin', 'accountant'],
        'FeePaymentController'        => ['super_admin', 'admin', 'accountant'],
        'PaymentController'           => ['super_admin', 'admin', 'accountant'],
        'ExpenseController'           => ['super_admin', 'admin', 'accountant'],
        'ExpenseCategoryController'   => ['super_admin', 'admin', 'accountant'],
        'BudgetController'            => ['super_admin', 'admin', 'accountant'],
        'LedgerController'            => ['super_admin', 'admin', 'accountant'],
        'BankReconciliationController'=> ['super_admin', 'admin', 'accountant'],
        'RefundController'            => ['super_admin', 'admin', 'accountant'],
        'ReportBuilderController'     => ['super_admin', 'admin', 'accountant'],
    ],
];
<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Tenancy
    |--------------------------------------------------------------------------
    |
    | The application is currently single-tenant (one school). This config
    | provides the foundation for future multi-institution (SaaS) support.
    | When `enabled` is false, the TenantScoped trait applies no filtering
    | and the application behaves exactly as before.
    |
    | To adopt multi-tenancy incrementally, set `enabled` to true and apply
    | the `TenantScoped` trait to each model that should be scoped per
    | institution, adding a nullable `tenant_id` column to its table.
    |
    */

    'enabled' => false,

    /*
     * How the active tenant is resolved when tenancy is enabled.
     * Supported: 'session' (session key `tenant_id`), 'auth' (auth user's
     * `tenant_id`), or 'header' (HTTP header `X-Tenant-Id`).
     */
    'resolver' => 'session',

    /*
     * Column name used on tenant-scoped tables.
     */
    'column' => 'tenant_id',
];

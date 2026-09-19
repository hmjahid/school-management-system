<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Foundation for multi-institution (SaaS) readiness.
 *
 * When tenancy is disabled (config('tenancy.enabled') === false) this trait
 * is a no-op and the application behaves exactly as the single-tenant system.
 * When enabled, every query against a TenantScoped model is automatically
 * filtered by the resolved tenant id, and new records are stamped with it.
 */
trait TenantScoped
{
    public static function bootTenantScoped(): void
    {
        if (! config('tenancy.enabled', false)) {
            return;
        }

        static::addGlobalScope('tenant', new class implements Scope
        {
            public function apply(Builder $builder, Model $model): void
            {
                $column = config('tenancy.column', 'tenant_id');
                $builder->where($model->qualifyColumn($column), self::resolveTenantId());
            }
        });

        static::creating(function (Model $model) {
            $column = config('tenancy.column', 'tenant_id');
            if (is_null($model->{$column})) {
                $model->{$column} = self::resolveTenantId();
            }
        });
    }

    public static function resolveTenantId(): ?int
    {
        $resolver = config('tenancy.resolver', 'session');

        return match ($resolver) {
            'auth' => auth()->user()?->tenant_id,
            'header' => (int) request()->header('X-Tenant-Id', 0) ?: null,
            default => session('tenant_id'),
        };
    }
}

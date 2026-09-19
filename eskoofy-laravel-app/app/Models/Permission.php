<?php

namespace App\Models;

use Spatie\Permission\Models\Permission as SpatiePermission;

class Permission extends SpatiePermission
{
    protected $fillable = ['name', 'description', 'guard_name'];

    public function isAssignedToRole(Role $role): bool
    {
        return $this->roles()->where('role_id', $role->id)->exists();
    }
}

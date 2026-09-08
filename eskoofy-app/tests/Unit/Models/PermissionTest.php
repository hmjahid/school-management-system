<?php

namespace Tests\Unit\Models;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PermissionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_persists_required_columns(): void
    {
        $permission = Permission::create([
            'name' => 'edit-reports-'.uniqid(),
            'guard_name' => 'web',
        ]);

        $this->assertDatabaseHas('permissions', [
            'id' => $permission->id,
            'name' => $permission->name,
            'guard_name' => 'web',
        ]);
    }

    #[Test]
    public function is_assigned_to_role_detects_membership(): void
    {
        $permission = Permission::create([
            'name' => 'view-dashboard-'.uniqid(),
            'guard_name' => 'web',
        ]);
        $role = Role::create([
            'name' => 'manager-'.uniqid(),
            'guard_name' => 'web',
        ]);

        $this->assertFalse($permission->isAssignedToRole($role));

        $role->givePermissionTo($permission);

        $this->assertTrue($permission->isAssignedToRole($role));
        $this->assertTrue($role->hasPermissionTo($permission->name));
    }

    #[Test]
    public function it_belongs_to_many_roles(): void
    {
        $permission = Permission::create([
            'name' => 'delete-posts-'.uniqid(),
            'guard_name' => 'web',
        ]);
        $role = Role::create([
            'name' => 'admin-'.uniqid(),
            'guard_name' => 'web',
        ]);

        $role->givePermissionTo($permission);

        $this->assertTrue($permission->roles->contains($role));
    }
}

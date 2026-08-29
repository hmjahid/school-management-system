<?php

namespace Tests\Unit\Models;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_persists_required_columns_and_description(): void
    {
        $role = Role::create([
            'name' => 'editor-' . uniqid(),
            'guard_name' => 'web',
            'description' => 'Content editor',
        ]);

        $this->assertDatabaseHas('roles', [
            'id' => $role->id,
            'name' => $role->name,
            'guard_name' => 'web',
            'description' => 'Content editor',
        ]);
    }

    /** @test */
    public function it_has_many_permissions(): void
    {
        $role = Role::create([
            'name' => 'moderator-' . uniqid(),
            'guard_name' => 'web',
        ]);
        $permission = Permission::create([
            'name' => 'moderate-' . uniqid(),
            'guard_name' => 'web',
        ]);

        $role->givePermissionTo($permission);

        $this->assertTrue($role->permissions->contains($permission));
    }

    /** @test */
    public function it_can_be_assigned_to_and_removed_from_users(): void
    {
        $user = User::factory()->create();
        $role = Role::create([
            'name' => 'staff-' . uniqid(),
            'guard_name' => 'web',
        ]);

        $user->assignRole($role);

        $this->assertTrue($user->hasRole($role));
        $this->assertTrue($role->users->contains($user));

        $user->removeRole($role);
        $this->assertFalse($user->fresh()->hasRole($role));
    }

    /** @test */
    public function it_links_user_through_school_role_relationship(): void
    {
        $role = Role::create([
            'name' => 'principal-' . uniqid(),
            'guard_name' => 'web',
        ]);
        $user = User::factory()->create();
        $user->role_id = $role->id;
        $user->save();

        $this->assertTrue($user->schoolRole->is($role));
    }
}

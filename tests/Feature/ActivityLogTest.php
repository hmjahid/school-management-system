<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_activity_page_shows_spatie_logs(): void
    {
        Role::create(['name' => 'admin']);
        \Spatie\Permission\Models\Permission::firstOrCreate(
            ['name' => 'view_audit_log', 'guard_name' => 'web'],
        );

        $user = User::factory()->create();
        $user->assignRole('admin');
        $user->givePermissionTo('view_audit_log');

        activity('admin_actions')
            ->causedBy($user)
            ->log('Updated school settings');

        $response = $this->actingAs($user)->get(route('dashboard.activity.index'));
        $response->assertStatus(200);
        $response->assertSee('Updated school settings', false);

        $this->assertSame(1, Activity::query()->where('description', 'Updated school settings')->count());
        $this->assertSame('Updated school settings', Activity::query()->orderByDesc('id')->first()->description);
        $this->assertSame($user->id, Activity::query()->orderByDesc('id')->first()->causer_id);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected Role $adminRole;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminRole = Role::factory()->create([
            'name' => 'Admin',
            'slug' => 'admin',
            'permissions' => ['users.view', 'users.create', 'users.edit', 'users.delete'],
            'backend_access' => true,
            'is_active' => true,
        ]);
        $this->admin = User::factory()->create(['is_active' => true]);
        $this->admin->roles()->attach($this->adminRole);
    }

    public function test_batch_activate_users()
    {
        $users = User::factory()->count(3)->create(['is_active' => false]);
        $userIds = $users->pluck('id')->toArray();

        $response = $this->actingAs($this->admin)->post('/admin/users/batch-activate', [
            'user_ids' => $userIds,
        ]);

        $response->assertRedirect();

        foreach ($userIds as $id) {
            $this->assertTrue(User::find($id)->is_active);
        }
    }

    public function test_batch_deactivate_users()
    {
        $users = User::factory()->count(3)->create(['is_active' => true]);
        $userIds = $users->pluck('id')->toArray();

        $response = $this->actingAs($this->admin)->post('/admin/users/batch-deactivate', [
            'user_ids' => $userIds,
        ]);

        $response->assertRedirect();

        foreach ($userIds as $id) {
            $this->assertFalse(User::find($id)->is_active);
        }
    }

    public function test_batch_delete_users()
    {
        $users = User::factory()->count(3)->create();
        $userIds = $users->pluck('id')->toArray();

        $response = $this->actingAs($this->admin)->post('/admin/users/batch-delete', [
            'user_ids' => $userIds,
        ]);

        $response->assertRedirect();

        foreach ($userIds as $id) {
            $this->assertNull(User::find($id));
            $this->assertNotNull(User::withTrashed()->find($id));
        }
    }

    public function test_logout_all_devices_increments_session_version()
    {
        $user = User::factory()->create(['session_version' => 1]);

        $response = $this->actingAs($this->admin)->post('/admin/users/'.$user->id.'/logout-all-devices');

        $response->assertRedirect();
        $this->assertEquals(2, $user->fresh()->session_version);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Models\VaultFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_vault_file_policy_force_delete_requires_admin()
    {
        $adminRole = Role::factory()->create([
            'slug' => 'admin',
            'permissions' => ['media.delete'],
        ]);
        $admin = User::factory()->create();
        $admin->roles()->attach($adminRole);

        $normalUser = User::factory()->create();

        $file = VaultFile::factory()->create();

        $this->assertTrue($admin->can('forceDelete', $file));
        $this->assertFalse($normalUser->can('forceDelete', $file));
    }

    public function test_vault_file_policy_restore_requires_admin()
    {
        $adminRole = Role::factory()->create([
            'slug' => 'admin',
            'permissions' => ['media.delete'],
        ]);
        $admin = User::factory()->create();
        $admin->roles()->attach($adminRole);

        $normalUser = User::factory()->create();

        $file = VaultFile::factory()->create();

        $this->assertTrue($admin->can('restore', $file));
        $this->assertFalse($normalUser->can('restore', $file));
    }

    public function test_vault_file_policy_update_any_requires_media_edit(): void
    {
        $normalUser = User::factory()->create();
        $this->assertFalse($normalUser->can('updateAny', VaultFile::class));

        $role = Role::factory()->create([
            'permissions' => ['media.edit'],
            'backend_access' => true,
            'is_active' => true,
        ]);
        $editor = User::factory()->create();
        $editor->roles()->attach($role);

        $this->assertTrue($editor->can('updateAny', VaultFile::class));
    }
}

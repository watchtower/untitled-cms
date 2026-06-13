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
}

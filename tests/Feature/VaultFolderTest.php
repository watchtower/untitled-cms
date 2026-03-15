<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Models\VaultFolder;
use Illuminate\Support\Str;
use Tests\TestCase;

class VaultFolderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        VaultFolder::truncate();
    }

    private function createAdminUser(): User
    {
        $user = User::factory()->create();
        $role = Role::updateOrCreate(
            ['slug' => 'super-admin'],
            ['name' => 'Super Admin', 'backend_access' => true, 'is_active' => true,
             'permissions' => ['media.view', 'media.create', 'media.edit', 'media.delete']]
        );
        $user->roles()->attach($role->id);
        return $user;
    }

    public function test_can_create_folder(): void
    {
        $user = $this->createAdminUser();

        $response = $this->actingAs($user)->postJson(route('admin.vault.folders.store'), [
            'name' => 'Finance',
        ]);

        $response->assertStatus(200)
            ->assertJson(['name' => 'Finance']);

        $this->assertDatabaseHas('vault_folders', ['name' => 'Finance'], 'mongodb');
    }

    public function test_can_create_nested_folder(): void
    {
        $user = $this->createAdminUser();

        $parent = VaultFolder::create([
            'uuid'     => Str::uuid()->toString(),
            'name'     => 'Parent',
            'owner_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->postJson(route('admin.vault.folders.store'), [
            'name'      => 'Child',
            'parent_id' => $parent->id,
        ]);

        $response->assertStatus(200)
            ->assertJson(['name' => 'Child', 'parent_id' => $parent->id]);
    }

    public function test_can_rename_folder(): void
    {
        $user = $this->createAdminUser();

        $folder = VaultFolder::create([
            'uuid'     => Str::uuid()->toString(),
            'name'     => 'Old Name',
            'owner_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->patchJson(route('admin.vault.folders.rename', $folder->id), [
            'name' => 'New Name',
        ]);

        $response->assertStatus(200)
            ->assertJson(['name' => 'New Name']);

        $this->assertEquals('New Name', $folder->fresh()->name);
    }

    public function test_can_delete_empty_folder(): void
    {
        $user = $this->createAdminUser();

        $folder = VaultFolder::create([
            'uuid'     => Str::uuid()->toString(),
            'name'     => 'To Delete',
            'owner_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->deleteJson(route('admin.vault.folders.destroy', $folder->id));

        $response->assertStatus(200);
        $this->assertSoftDeleted($folder);
    }
}

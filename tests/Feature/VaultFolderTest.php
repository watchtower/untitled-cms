<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\VaultFolder;
use Tests\TestCase;

class VaultFolderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        VaultFolder::truncate();
    }

    public function test_can_create_folder()
    {
        $user = User::factory()->create();
        $role = \App\Models\Role::firstOrCreate(['name' => 'Super Admin', 'slug' => 'super-admin']);
        $user->roles()->attach($role->id);

        $response = $this->actingAs($user)->postJson(route('vault.folders.store'), [
            'name' => 'Finance',
        ]);

        $response->assertStatus(200)
            ->assertJson(['name' => 'Finance']);

        $this->assertDatabaseHas('vault_folders', ['name' => 'Finance'], 'mongodb');
    }

    public function test_can_create_nested_folder()
    {
        $user = User::factory()->create();
        $role = \App\Models\Role::firstOrCreate(['name' => 'Super Admin', 'slug' => 'super-admin']);
        $user->roles()->attach($role->id);

        $parent = VaultFolder::create([
            'uuid' => 'parent-uuid',
            'name' => 'Parent',
            'owner_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->postJson(route('vault.folders.store'), [
            'name' => 'Child',
            'parent_id' => $parent->id,
        ]);

        $response->assertStatus(200)
            ->assertJson(['name' => 'Child', 'parent_id' => $parent->id]);
    }

    public function test_can_rename_folder()
    {
        $user = User::factory()->create();
        $role = \App\Models\Role::firstOrCreate(['name' => 'Super Admin', 'slug' => 'super-admin']);
        $user->roles()->attach($role->id);

        $folder = VaultFolder::create([
            'uuid' => 'folder-uuid',
            'name' => 'Old Name',
            'owner_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->patchJson(route('vault.folders.rename', $folder->id), [
            'name' => 'New Name',
        ]);

        $response->assertStatus(200)
            ->assertJson(['name' => 'New Name']);

        $this->assertEquals('New Name', $folder->fresh()->name);
    }

    public function test_can_delete_empty_folder()
    {
        $user = User::factory()->create();
        $role = \App\Models\Role::firstOrCreate(['name' => 'Super Admin', 'slug' => 'super-admin']);
        $user->roles()->attach($role->id);

        $folder = VaultFolder::create([
            'uuid' => 'folder-uuid',
            'name' => 'To Delete',
            'owner_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->deleteJson(route('vault.folders.destroy', $folder->id));

        $response->assertStatus(200);
        $this->assertSoftDeleted($folder);
    }
}

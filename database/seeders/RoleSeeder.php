<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'users.manage',
            'roles.manage',
            'pages.view',
            'pages.create',
            'pages.edit',
            'pages.delete',
            'pages.publish',
            'media.view',
            'media.create',
            'media.edit',
            'media.delete',
            'banners.manage',
            'ai-integrations.view',
            'ai-integrations.edit',
        ];

        // 2. Create Roles and Assign Permissions

        // Admin: All permissions
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $adminRole->syncPermissions($permissions);

        // Editor: Can manage content/banners, but not users/roles
        $editorRole = Role::firstOrCreate(['name' => 'editor']);
        $editorRole->syncPermissions([
            'pages.view',
            'pages.create',
            'pages.edit',
            'pages.delete',
            'pages.publish',
            'media.view',
            'media.create',
            'media.edit',
            'media.delete',
            'banners.manage',
        ]);

        // Author: Can create/edit own content, no publishing or deleting others
        $authorRole = Role::firstOrCreate(['name' => 'author']);
        $authorRole->syncPermissions([
            'pages.view',
            'pages.create',
            'pages.edit',
            'media.view',
            'media.create',
        ]);
    }
}

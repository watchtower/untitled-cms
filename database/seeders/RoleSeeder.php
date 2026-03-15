<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // Admin: All permissions — always kept in sync with Role::availablePermissions()
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $adminRole->syncPermissions(Role::availablePermissions());

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

        // User: Self-registered users — read-only access
        $userRole = Role::firstOrCreate(['name' => 'user'], ['slug' => 'user']);
        $userRole->syncPermissions([
            'pages.view',
            'media.view',
        ]);
    }
}

<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Create/Update Roles
        $adminRole = Role::updateOrCreate(
            ['slug' => 'admin'],
            [
                'name' => 'Admin',
                'permissions' => [
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
                ],
            ]
        );

        $editorRole = Role::updateOrCreate(
            ['slug' => 'editor'],
            [
                'name' => 'Editor',
                'permissions' => [
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
                ],
            ]
        );

        $authorRole = Role::updateOrCreate(
            ['slug' => 'author'],
            [
                'name' => 'Author',
                'permissions' => [
                    'pages.view',
                    'pages.create',
                    'pages.edit', // Own only check in policy
                    'media.view',
                    'media.create',
                ],
            ]
        );

        // User: self-registered users — read-only access
        Role::updateOrCreate(
            ['slug' => 'user'],
            [
                'name' => 'User',
                'permissions' => [
                    'pages.view',
                    'media.view',
                ],
            ]
        );

        // 2. Create/Update Admin User
        $adminUser = User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        // 3. Assign Role
        // Use sync to ensure the user has exactly this role without duplicates
        $adminUser->roles()->sync([$adminRole->id]);

        $this->command->info('Roles and Admin User seeded/updated successfully!');
    }
}

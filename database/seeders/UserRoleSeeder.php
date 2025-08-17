<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserRoleSeeder extends Seeder
{
    public function run(): void
    {
        // Update existing users to have admin role if no role is set
        User::whereNull('role')->orWhere('role', '')->update(['role' => 'admin']);

        // Create or update super admin user
        User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Super Admin',
                'password' => bcrypt('password'),
                'role' => 'super_admin',
                'email_verified_at' => now(),
                'status' => 'active',
            ]
        );
    }
}

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

        // Create a super admin user if it doesn't exist
        if (! User::where('role', 'super_admin')->exists()) {
            User::create([
                'name' => 'Super Admin',
                'email' => 'admin@example.com',
                'password' => bcrypt('password'),
                'role' => 'super_admin',
                'email_verified_at' => now(),
            ]);
        }
    }
}

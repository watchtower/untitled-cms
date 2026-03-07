<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Role;
use App\Models\Setting;
use App\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Inertia\Testing\AssertableInertia as Assert;

class MaintenanceModeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Ensure roles exist
        Role::firstOrCreate(['name' => 'super-admin']);
        Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'user']);
    }

    protected function tearDown(): void
    {
        // Clean up settings that were changed
        $settings = app(SettingsService::class);
        $settings->set('maintenance_mode', false);

        // Delete test users and roles
        $users = User::whereIn('email', [''])->get(); // Not matching anything specifically since we used factory, let's just delete the ones without emails or mock them
        // Instead of deleting users, we could just clear out the users created by factory

        parent::tearDown();
    }

    public function test_public_pages_return_503_when_maintenance_mode_is_enabled()
    {
        // Enable maintenance mode
        Setting::updateOrCreate(['key' => 'maintenance_mode'], ['value' => true]);
        app(SettingsService::class)->clearCache();

        // Access public page
        $response = $this->get('/');

        // Should return 503 instead of 200
        $response->assertStatus(503);
    }

    public function test_auth_routes_are_accessible_during_maintenance()
    {
        Setting::updateOrCreate(['key' => 'maintenance_mode'], ['value' => true]);
        app(SettingsService::class)->clearCache();

        // Login should be accessible
        $response = $this->get('/login');
        $response->assertStatus(200);
    }

    public function test_admin_can_access_pages_during_maintenance()
    {
        Setting::updateOrCreate(['key' => 'maintenance_mode'], ['value' => true]);
        app(SettingsService::class)->clearCache();

        $admin = User::factory()->create();
        $adminRole = Role::where('name', 'admin')->first();
        $admin->roles()->attach($adminRole);

        $response = $this->actingAs($admin)->get('/');

        // Should be OK because they are an admin
        $response->assertStatus(200);
    }

    public function test_super_admin_can_access_pages_during_maintenance()
    {
        Setting::updateOrCreate(['key' => 'maintenance_mode'], ['value' => true]);
        app(SettingsService::class)->clearCache();

        $superAdmin = User::factory()->create();
        $superAdminRole = Role::where('name', 'super-admin')->first();
        $superAdmin->roles()->attach($superAdminRole);

        $response = $this->actingAs($superAdmin)->get('/');

        $response->assertStatus(200);
    }

    public function test_regular_user_cannot_access_pages_during_maintenance()
    {
        Setting::updateOrCreate(['key' => 'maintenance_mode'], ['value' => true]);
        app(SettingsService::class)->clearCache();

        $user = User::factory()->create();
        $userRole = Role::where('name', 'user')->first();
        $user->roles()->attach($userRole);

        // They can access login/dashboard normally
        $response = $this->actingAs($user)->get('/');

        $response->assertStatus(503);
    }

    public function test_pages_are_accessible_when_maintenance_mode_is_disabled()
    {
        Setting::updateOrCreate(['key' => 'maintenance_mode'], ['value' => false]);
        app(SettingsService::class)->clearCache();

        $response = $this->get('/');

        $response->assertStatus(200);
    }
}

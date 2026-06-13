<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PageControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        User::truncate();
        Role::truncate();
        Page::truncate();

        $role = Role::factory()->create([
            'slug' => 'admin',
            'permissions' => ['pages.view', 'pages.create', 'pages.edit', 'pages.delete'],
            'backend_access' => true,
            'is_active' => true,
        ]);
        $this->admin = User::factory()->create(['is_active' => true]);
        $this->admin->roles()->attach($role);
    }

    public function test_admin_can_create_page()
    {
        $response = $this->actingAs($this->admin)->post('/admin/pages', [
            'title' => 'Test Page',
            'slug' => 'test-page',
            'content' => '<p>Content</p>',
            'status' => 'published',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect('/admin/pages');
        $this->assertTrue(Page::where('title', 'Test Page')->exists());
    }

    public function test_admin_can_update_page()
    {
        $page = Page::factory()->create(['title' => 'Old Title']);

        $response = $this->actingAs($this->admin)->put('/admin/pages/'.$page->id, [
            'title' => 'New Title',
            'slug' => 'new-title',
            'content' => '<p>Updated</p>',
            'status' => 'draft',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect('/admin/pages');
        if (Page::where('title', 'Old Title')->exists()) {
            dump(Page::all()->toArray());
        }
        $this->assertTrue(Page::where('title', 'New Title')->exists());
        $this->assertFalse(Page::where('title', 'Old Title')->exists());
    }
}

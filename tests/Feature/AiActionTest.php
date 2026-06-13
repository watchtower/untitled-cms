<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiActionTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::factory()->create([
            'name' => 'Admin',
            'slug' => 'admin',
            'permissions' => ['pages.create', 'pages.update'],
            'backend_access' => true,
            'is_active' => true,
        ]);
        $this->admin = User::factory()->create(['is_active' => true]);
        $this->admin->roles()->attach($role);
    }

    public function test_execute_creates_a_page_correctly()
    {
        $proposal = [
            'action' => 'create_page',
            'params' => [
                'title' => 'About Us',
                'content' => '<p>About us content</p>',
            ],
        ];

        $response = $this->actingAs($this->admin)->postJson('/admin/ai/actions/execute', [
            'proposal' => $proposal,
        ]);

        $response->assertStatus(200);
        $this->assertTrue(Page::where('title', 'About Us')->exists());
    }

    public function test_execute_updates_the_correct_page_not_fuzzy_match()
    {
        // Two similar pages
        Page::factory()->create(['title' => 'Test Page', 'status' => 'published']);
        $pageToUpdate = Page::factory()->create(['title' => 'Test', 'status' => 'published']);

        $proposal = [
            'action' => 'update_page',
            'resolved_id' => $pageToUpdate->id,
            'params' => [
                'title' => 'Test',
                'content' => '<p>Updated content</p>',
            ],
        ];

        $response = $this->actingAs($this->admin)->postJson('/admin/ai/actions/execute', [
            'proposal' => $proposal,
        ]);

        if ($response->status() !== 200) {
            $response->dump();
        }

        $response->assertStatus(200);

        $this->assertTrue(Page::where('title', 'Test')->where('content', '<p>Updated content</p>')->exists());

        // The fuzzy match should not have altered 'Test Page'
        $this->assertFalse(Page::where('title', 'Test Page')->where('content', '<p>Updated content</p>')->exists());
    }

    public function test_unsupported_actions_are_rejected()
    {
        $proposal = [
            'action' => 'delete_database',
            'params' => [],
        ];

        $response = $this->actingAs($this->admin)->postJson('/admin/ai/actions/execute', [
            'proposal' => $proposal,
        ]);

        $response->assertStatus(422);
    }

    public function test_authorization_is_enforced()
    {
        // Normal user without admin role
        $user = User::factory()->create();

        $proposal = [
            'action' => 'create_page',
            'params' => [
                'title' => 'Hacked',
                'content' => '<p>Hacked content</p>',
            ],
        ];

        $response = $this->actingAs($user)->postJson('/admin/ai/actions/execute', [
            'proposal' => $proposal,
        ]);

        $response->assertStatus(403);
    }
}

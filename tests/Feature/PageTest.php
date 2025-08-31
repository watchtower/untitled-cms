<?php

use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;



test('admin can create a page', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $response = $this->actingAs($admin)->post(route('admin.pages.store'), [
        'title' => 'New Page',
        'content' => 'This is the content of the new page.',
        'status' => 'published',
    ]);

    $response->assertRedirect(route('admin.pages.index'));
    $this->assertDatabaseHas('pages', [
        'title' => 'New Page',
    ]);
});

test('admin can update a page', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $page = Page::factory()->create();

    $response = $this->actingAs($admin)->put(route('admin.pages.update', $page), [
        'title' => 'Updated Page',
        'content' => 'This is the updated content.',
        'status' => 'draft',
    ]);

    $response->assertRedirect(route('admin.pages.index'));
    $this->assertDatabaseHas('pages', [
        'title' => 'Updated Page',
    ]);
});

test('admin can delete a page', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $page = Page::factory()->create();

    $response = $this->actingAs($admin)->delete(route('admin.pages.destroy', $page));

    $response->assertRedirect(route('admin.pages.index'));
    $this->assertSoftDeleted($page);
});

test('admin can publish a page', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $page = Page::factory()->create(['status' => 'draft']);

    $response = $this->actingAs($admin)->patch(route('admin.pages.publish', $page));

    $response->assertRedirect();
    $this->assertDatabaseHas('pages', [
        'id' => $page->id,
        'status' => 'published',
    ]);
});

test('admin can unpublish a page', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $page = Page::factory()->create(['status' => 'published']);

    $response = $this->actingAs($admin)->patch(route('admin.pages.unpublish', $page));

    $response->assertRedirect();
    $this->assertDatabaseHas('pages', [
        'id' => $page->id,
        'status' => 'draft',
    ]);
});

test('admin can duplicate a page', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $page = Page::factory()->create();

    $response = $this->actingAs($admin)->post(route('admin.pages.duplicate', $page));

    $response->assertRedirect();
    $this->assertDatabaseHas('pages', [
        'title' => $page->title . ' (Copy)',
    ]);
});

test('user cannot create a page', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('admin.pages.store'), [
        'title' => 'New Page',
        'content' => 'This is the content of the new page.',
        'status' => 'published',
    ]);

    $response->assertForbidden();
});

test('user cannot update a page', function () {
    $user = User::factory()->create();
    $page = Page::factory()->create();

    $response = $this->actingAs($user)->put(route('admin.pages.update', $page), [
        'title' => 'Updated Page',
        'content' => 'This is the updated content.',
        'status' => 'draft',
    ]);

    $response->assertForbidden();
});

test('user cannot delete a page', function () {
    $user = User::factory()->create();
    $page = Page::factory()->create();

    $response = $this->actingAs($user)->delete(route('admin.pages.destroy', $page));

    $response->assertForbidden();
});

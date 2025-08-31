<?php

use App\Models\Category;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;



test('admin can create a category', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $response = $this->actingAs($admin)->post(route('admin.taxonomy.store'), [
        'name' => 'New Category',
        'type' => 'category',
    ]);

    $response->assertRedirect(route('admin.taxonomy.index', ['tab' => 'categories']));
    $this->assertDatabaseHas('categories', [
        'name' => 'New Category',
    ]);
});

test('admin can update a category', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $category = Category::factory()->create();

    $response = $this->actingAs($admin)->put(route('admin.taxonomy.update', ['type' => 'category', 'id' => $category->id]), [
        'name' => 'Updated Category',
    ]);

    $response->assertRedirect(route('admin.taxonomy.index', ['tab' => 'categories']));
    $this->assertDatabaseHas('categories', [
        'name' => 'Updated Category',
    ]);
});

test('admin can delete a category', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $category = Category::factory()->create();

    $response = $this->actingAs($admin)->delete(route('admin.taxonomy.destroy', ['type' => 'category', 'id' => $category->id]));

    $response->assertRedirect(route('admin.taxonomy.index', ['tab' => 'categories']));
    $this->assertDatabaseMissing('categories', [
        'id' => $category->id,
    ]);
});

test('admin can create a tag', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $response = $this->actingAs($admin)->post(route('admin.taxonomy.store'), [
        'name' => 'New Tag',
        'type' => 'tag',
    ]);

    $response->assertRedirect(route('admin.taxonomy.index', ['tab' => 'tags']));
    $this->assertDatabaseHas('tags', [
        'name' => 'New Tag',
    ]);
});

test('admin can update a tag', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $tag = Tag::factory()->create();

    $response = $this->actingAs($admin)->put(route('admin.taxonomy.update', ['type' => 'tag', 'id' => $tag->id]), [
        'name' => 'Updated Tag',
    ]);

    $response->assertRedirect(route('admin.taxonomy.index', ['tab' => 'tags']));
    $this->assertDatabaseHas('tags', [
        'name' => 'Updated Tag',
    ]);
});

test('admin can delete a tag', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $tag = Tag::factory()->create();

    $response = $this->actingAs($admin)->delete(route('admin.taxonomy.destroy', ['type' => 'tag', 'id' => $tag->id]));

    $response->assertRedirect(route('admin.taxonomy.index', ['tab' => 'tags']));
    $this->assertDatabaseMissing('tags', [
        'id' => $tag->id,
    ]);
});

test('user cannot create a category', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('admin.taxonomy.store'), [
        'name' => 'New Category',
        'type' => 'category',
    ]);

    $response->assertForbidden();
});

test('user cannot create a tag', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('admin.taxonomy.store'), [
        'name' => 'New Tag',
        'type' => 'tag',
    ]);

    $response->assertForbidden();
});
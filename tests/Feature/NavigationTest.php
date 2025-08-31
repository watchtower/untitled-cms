<?php

use App\Models\NavigationItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;



test('admin can create a navigation item', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $response = $this->actingAs($admin)->post(route('admin.navigation.store'), [
        'label' => 'New Item',
        'type' => 'url',
        'url' => '/new-item',
        'is_visible' => true,
        'opens_new_tab' => false,
    ]);

    $response->assertRedirect(route('admin.navigation.index'));
    $this->assertDatabaseHas('navigation_items', [
        'label' => 'New Item',
    ]);
});

test('admin can update a navigation item', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $item = NavigationItem::factory()->create();

    $response = $this->actingAs($admin)->put(route('admin.navigation.update', $item), [
        'label' => 'Updated Item',
        'type' => 'url',
        'url' => '/updated-item',
        'is_visible' => true,
        'opens_new_tab' => false,
    ]);

    $response->assertRedirect(route('admin.navigation.index'));
    $this->assertDatabaseHas('navigation_items', [
        'label' => 'Updated Item',
    ]);
});

test('admin can delete a navigation item', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $item = NavigationItem::factory()->create();

    $response = $this->actingAs($admin)->delete(route('admin.navigation.destroy', $item));

    $response->assertRedirect(route('admin.navigation.index'));
    $this->assertDatabaseMissing('navigation_items', [
        'id' => $item->id,
    ]);
});

test('user cannot create a navigation item', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('admin.navigation.store'), [
        'label' => 'New Item',
        'type' => 'url',
        'url' => '/new-item',
        'is_visible' => true,
        'opens_new_tab' => false,
    ]);

    $response->assertForbidden();
});

test('user cannot update a navigation item', function () {
    $user = User::factory()->create();
    $item = NavigationItem::factory()->create();

    $response = $this->actingAs($user)->put(route('admin.navigation.update', $item), [
        'label' => 'Updated Item',
        'type' => 'url',
        'url' => '/updated-item',
        'is_visible' => true,
        'opens_new_tab' => false,
    ]);

    $response->assertForbidden();
});

test('user cannot delete a navigation item', function () {
    $user = User::factory()->create();
    $item = NavigationItem::factory()->create();

    $response = $this->actingAs($user)->delete(route('admin.navigation.destroy', $item));

    $response->assertForbidden();
});
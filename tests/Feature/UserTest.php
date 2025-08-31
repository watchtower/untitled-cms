<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;



test('admin can create a user', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $response = $this->actingAs($admin)->post(route('admin.users.store'), [
        'name' => 'New User',
        'email' => 'newuser@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'role' => 'editor',
        'status' => 'active',
    ]);

    $response->assertRedirect(route('admin.users.index'));
    $this->assertDatabaseHas('users', [
        'name' => 'New User',
        'email' => 'newuser@example.com',
    ]);
});

test('admin can update a user', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $user = User::factory()->create();

    $response = $this->actingAs($admin)->put(route('admin.users.update', $user), [
        'name' => 'Updated User',
        'email' => 'updateduser@example.com',
        'role' => 'editor',
        'status' => 'active',
        'subscription_level_id' => null,
        'subscription_active' => false,
        'email_verified' => false,
    ]);

    $response->assertRedirect(route('admin.users.index'));
    $this->assertDatabaseHas('users', [
        'name' => 'Updated User',
        'email' => 'updateduser@example.com',
    ]);
});

test('admin can delete a user', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $user = User::factory()->create();

    $response = $this->actingAs($admin)->delete(route('admin.users.destroy', $user));

    $response->assertRedirect(route('admin.users.index'));
    $this->assertSoftDeleted($user);
});

test('user cannot create a user', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('admin.users.store'), [
        'name' => 'New User',
        'email' => 'newuser@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'role' => 'editor',
        'status' => 'active',
    ]);

    $response->assertForbidden();
});

test('user cannot update a user', function () {
    $user = User::factory()->create();
    $anotherUser = User::factory()->create();

    $response = $this->actingAs($user)->put(route('admin.users.update', $anotherUser), [
        'name' => 'Updated User',
        'email' => 'updateduser@example.com',
        'role' => 'editor',
        'status' => 'active',
    ]);

    $response->assertForbidden();
});

test('user cannot delete a user', function () {
    $user = User::factory()->create();
    $anotherUser = User::factory()->create();

    $response = $this->actingAs($user)->delete(route('admin.users.destroy', $anotherUser));

    $response->assertForbidden();
});
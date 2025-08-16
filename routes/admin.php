<?php

use App\Http\Controllers\Admin\MediaController;
use App\Http\Controllers\Admin\NavigationController;
use App\Http\Controllers\Admin\PageController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', function () {
        $stats = [
            'total_pages' => \App\Models\Page::count(),
            'published_pages' => \App\Models\Page::where('status', 'published')->count(),
            'draft_pages' => \App\Models\Page::where('status', 'draft')->count(),
            'total_users' => \App\Models\User::count(),
            'navigation_items' => \App\Models\NavigationItem::count(),
            'media_files' => \App\Models\Media::count(),
        ];

        return view('admin.dashboard', compact('stats'));
    })->name('dashboard');

    Route::resource('pages', PageController::class);
    Route::post('/pages/{page}/duplicate', [PageController::class, 'duplicate'])->name('pages.duplicate');
    Route::patch('/pages/{page}/publish', [PageController::class, 'publish'])->name('pages.publish');
    Route::patch('/pages/{page}/unpublish', [PageController::class, 'unpublish'])->name('pages.unpublish');
    Route::get('/pages/{page}/preview', [PageController::class, 'preview'])->name('pages.preview');

    Route::resource('navigation', NavigationController::class);
    Route::put('/navigation/order', [NavigationController::class, 'updateOrder'])->name('navigation.order');

    Route::resource('media', MediaController::class)->except(['create', 'edit']);

    Route::resource('users', UserController::class);
    Route::patch('/users/{user}/activate', [UserController::class, 'activate'])->name('users.activate');
    Route::patch('/users/{user}/deactivate', [UserController::class, 'deactivate'])->name('users.deactivate');
    Route::patch('/users/{user}/restore', [UserController::class, 'restore'])->name('users.restore');
    Route::delete('/users/{user}/force-delete', [UserController::class, 'forceDelete'])->name('users.force-delete');
    Route::patch('/users/{user}/verify-email', [UserController::class, 'verifyEmail'])->name('users.verify-email');
    Route::patch('/users/{user}/unverify-email', [UserController::class, 'unverifyEmail'])->name('users.unverify-email');

    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');
});

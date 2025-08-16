<?php

use App\Http\Controllers\Admin\NavigationController;
use App\Http\Controllers\Admin\PageController;
use App\Http\Controllers\Admin\SettingsController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', function () {
        $stats = [
            'total_pages' => \App\Models\Page::count(),
            'published_pages' => \App\Models\Page::where('status', 'published')->count(),
            'draft_pages' => \App\Models\Page::where('status', 'draft')->count(),
            'total_users' => \App\Models\User::count(),
            'navigation_items' => \App\Models\NavigationItem::count(),
        ];

        return view('admin.dashboard', compact('stats'));
    })->name('dashboard');

    Route::resource('pages', PageController::class);
    Route::post('/pages/{page}/duplicate', [PageController::class, 'duplicate'])->name('pages.duplicate');

    Route::resource('navigation', NavigationController::class);
    Route::put('/navigation/order', [NavigationController::class, 'updateOrder'])->name('navigation.order');

    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');
});

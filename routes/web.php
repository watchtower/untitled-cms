<?php

use App\Http\Controllers\PageController;
use App\Http\Controllers\ProfileController;
use App\Models\NavigationItem;

Route::get('/', [PageController::class, 'home'])->name('home');

Route::get('/dashboard', function () {
    $navigationItems = NavigationItem::with(['page', 'children.page'])
        ->topLevel()
        ->ordered()
        ->get();

    return view('dashboard', compact('navigationItems'));
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

// Page routes (must be last to catch all remaining slugs)
// Exclude admin routes from being caught by the page slug route
Route::get('/{slug}', [PageController::class, 'show'])
    ->where('slug', '^(?!admin).*$')
    ->name('page.show');

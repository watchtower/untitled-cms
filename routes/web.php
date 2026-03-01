<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Custom user routes (must be before resource route)
    Route::post('/users/invite', [\App\Http\Controllers\UserController::class, 'invite'])->name('users.invite');
    Route::post('/users/batch-activate', [\App\Http\Controllers\UserController::class, 'batchActivate'])->name('users.batch-activate');
    Route::post('/users/batch-deactivate', [\App\Http\Controllers\UserController::class, 'batchDeactivate'])->name('users.batch-deactivate');
    Route::post('/users/batch-delete', [\App\Http\Controllers\UserController::class, 'batchDelete'])->name('users.batch-delete');

    Route::post('/users/{id}/restore', [\App\Http\Controllers\UserController::class, 'restore'])->name('users.restore');
    Route::delete('/users/{id}/force-delete', [\App\Http\Controllers\UserController::class, 'forceDelete'])->name('users.force-delete');
    Route::post('/users/{id}/logout-all-devices', [\App\Http\Controllers\UserController::class, 'logoutAllDevices'])->name('users.logout-all-devices');

    Route::resource('users', \App\Http\Controllers\UserController::class);
    Route::resource('roles', \App\Http\Controllers\RoleController::class);
    Route::resource('pages', \App\Http\Controllers\PageController::class);

    Route::resource('banners', \App\Http\Controllers\BannerController::class);
    Route::resource('ai-hubs', \App\Http\Controllers\AiHubController::class)->only(['index', 'update']);
    Route::post('/ai-hubs/{aiHub}/activate', [\App\Http\Controllers\AiHubController::class, 'activate'])->name('ai-hubs.activate');

    Route::post('/ai/generate-seo', [\App\Http\Controllers\AiController::class, 'generateSeo'])->name('ai.seo');
    Route::post('/ai/generate-tags', [\App\Http\Controllers\AiController::class, 'generateTags'])->name('ai.generate-tags');
    Route::post('/ai/generate-social-image', [\App\Http\Controllers\AiController::class, 'generateSocialImage'])->name('ai.social-image');
    Route::post('/ai/generate-alt-text', [\App\Http\Controllers\AiController::class, 'generateAltText'])->name('ai.alt-text');
    Route::post('/ai/generate', [\App\Http\Controllers\AiController::class, 'generate'])->name('ai.generate');
    Route::post('/ai/chat', [\App\Http\Controllers\AiController::class, 'chat'])->name('ai.chat');
    Route::post('/ai/generate-image', [\App\Http\Controllers\AiController::class, 'generateImage'])->name('ai.generate-image');
    Route::get('/ai/context', [\App\Http\Controllers\AiContextController::class, 'show'])->name('ai.context');

    // AI Chat Sessions
    Route::get('/ai/chat/sessions', [\App\Http\Controllers\ChatSessionController::class, 'index'])->name('ai.sessions.index');
    Route::post('/ai/chat/sessions', [\App\Http\Controllers\ChatSessionController::class, 'store'])->name('ai.sessions.store');
    Route::get('/ai/chat/sessions/{id}', [\App\Http\Controllers\ChatSessionController::class, 'show'])->name('ai.sessions.show');
    Route::put('/ai/chat/sessions/{id}', [\App\Http\Controllers\ChatSessionController::class, 'update'])->name('ai.sessions.update');
    Route::delete('/ai/chat/sessions/{id}', [\App\Http\Controllers\ChatSessionController::class, 'destroy'])->name('ai.sessions.destroy');

    // AI Actions (Phase 2)
    Route::post('/ai/actions/resolve', [\App\Http\Controllers\AiActionController::class, 'resolve'])->name('ai.actions.resolve');
    Route::post('/ai/actions/parse', [\App\Http\Controllers\AiActionController::class, 'parse'])->name('ai.actions.parse');
    Route::post('/ai/actions/execute', [\App\Http\Controllers\AiActionController::class, 'execute'])->name('ai.actions.execute');
    Route::post('/ai/actions/revert/{logId}', [\App\Http\Controllers\AiActionController::class, 'revert'])->name('ai.actions.revert');


    Route::get('/activity-log', [\App\Http\Controllers\ActivityLogController::class, 'index'])->name('activity-log.index');

    Route::get('/settings', [\App\Http\Controllers\SettingController::class, 'index'])->name('settings.index');
    Route::put('/settings/{key}', [\App\Http\Controllers\SettingController::class, 'update'])->name('settings.update');

    // Vault Manager Routes
    Route::prefix('vault')->name('vault.')->group(function () {
        Route::get('/', [\App\Http\Controllers\VaultController::class, 'adminPage'])->name('index');
        Route::get('/files', [\App\Http\Controllers\VaultController::class, 'list'])->name('files.list');
        Route::get('/trash', [\App\Http\Controllers\VaultController::class, 'trash'])->name('trash.list');
        Route::post('/upload', [\App\Http\Controllers\VaultController::class, 'upload'])->name('upload');
        Route::post('/save-ai-image', [\App\Http\Controllers\VaultController::class, 'saveAiImage'])->name('save-ai-image');
        Route::get('/file/{uuid}', [\App\Http\Controllers\VaultController::class, 'serve'])->name('file.serve');
        Route::post('/files/batch-move', [\App\Http\Controllers\VaultController::class, 'batchMove'])->name('files.batch_move');
        Route::post('/files/batch-delete', [\App\Http\Controllers\VaultController::class, 'batchDelete'])->name('files.batch_delete');
        Route::post('/generate-alt-text', [\App\Http\Controllers\VaultController::class, 'generateMissingAltText'])->name('generate-alt-text');
        Route::delete('/file/{uuid}', [\App\Http\Controllers\VaultController::class, 'destroy'])->name('file.destroy');
        Route::post('/file/{uuid}/restore', [\App\Http\Controllers\VaultController::class, 'restore'])->name('file.restore');
        Route::delete('/file/{uuid}/force', [\App\Http\Controllers\VaultController::class, 'forceDestroy'])->name('file.force_destroy');
        Route::patch('/file/{uuid}/rename', [\App\Http\Controllers\VaultController::class, 'rename'])->name('file.rename');
        Route::patch('/file/{uuid}/move', [\App\Http\Controllers\VaultController::class, 'move'])->name('file.move');
        Route::patch('/file/{uuid}/alt-text', [\App\Http\Controllers\VaultController::class, 'updateAltText'])->name('file.alt_text');
        Route::patch('/file/{uuid}/toggle-optimization', [\App\Http\Controllers\VaultController::class, 'toggleOptimization'])->name('file.toggle_optimization');

        Route::get('/folders', [\App\Http\Controllers\VaultFolderController::class, 'list'])->name('folders.list');
        Route::post('/folders', [\App\Http\Controllers\VaultFolderController::class, 'store'])->name('folders.store');
        Route::patch('/folders/{id}/rename', [\App\Http\Controllers\VaultFolderController::class, 'rename'])->name('folders.rename');
        Route::delete('/folders/{id}', [\App\Http\Controllers\VaultFolderController::class, 'destroy'])->name('folders.destroy');
    });
});

require __DIR__ . '/auth.php';

// Sitemap
Route::get('/sitemap.md', [\App\Http\Controllers\SitemapController::class, 'markdown'])->name('sitemap.md');

// RSS Feed
Route::get('/rss', [\App\Http\Controllers\FeedController::class, 'rss'])->name('feed.rss');
Route::get('/feed', [\App\Http\Controllers\FeedController::class, 'rss']);

// Public Routes (Must be last to allow {slug} wildcard)
Route::controller(\App\Http\Controllers\PublicController::class)->group(function () {
    Route::get('/', 'home')->name('home');
    Route::get('/{slug}', 'show')->name('public.page');
});

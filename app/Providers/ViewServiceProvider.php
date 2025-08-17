<?php

namespace App\Providers;

use App\Models\NavigationItem;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class ViewServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('layouts.navigation', function ($view) {
            $navigationItems = NavigationItem::with(['page', 'children.page'])
                ->topLevel()
                ->ordered()
                ->where('is_visible', true)
                ->get();
            $view->with('navigationItems', $navigationItems);
        });
    }
}

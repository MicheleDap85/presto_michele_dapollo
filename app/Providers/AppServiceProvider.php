<?php

namespace App\Providers;

use App\Models\Category;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
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
        Paginator::useBootstrapFive();

        View::composer(['components.navbar', 'welcome'], function ($view): void {
            try {
                if (Schema::hasTable('categories')) {
                    $view->with('categories', Category::query()->orderBy('name')->get());
                }
            } catch (\Throwable $exception) {
                // The database may not be available yet during install or test bootstrap.
            }
        });
    }
}

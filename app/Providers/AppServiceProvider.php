<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Category;
use App\Policies\CategoryPolicy;
use App\Models\Series;
use App\Policies\SeriesPolicy;
use App\Models\Product;
use App\Policies\ProductPolicy;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;

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
        Blade::anonymousComponentNamespace(
            'admin.components',
            'admin'
        );

        Gate::policy(Category::class, CategoryPolicy::class);
        Gate::policy(Series::class, SeriesPolicy::class);
        Gate::policy(Product::class, ProductPolicy::class);
    }
}

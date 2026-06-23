<?php

namespace App\Providers;

use Illuminate\Support\Facades\Blade;
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
        Blade::if('permission', fn (string $code): bool => auth()->user()?->hasPermission($code) ?? false);
        Blade::if('anypermission', fn (...$codes): bool => auth()->user()?->hasAnyPermission($codes) ?? false);
    }
}

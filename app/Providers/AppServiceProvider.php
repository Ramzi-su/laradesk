<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
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
        // Outside production, fail loudly on N+1 lazy loading, on attributes silently
        // discarded by mass-assignment protection and on attributes that were not loaded.
        Model::shouldBeStrict(! $this->app->isProduction());

        // Guards the whole /admin area (route middleware "can:admin").
        Gate::define('admin', fn (User $user): bool => $user->isAdmin());
    }
}

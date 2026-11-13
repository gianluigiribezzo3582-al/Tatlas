<?php

namespace App\Providers;

use App\Models\Artist;
use App\Models\Studio;
use App\Models\User;
use App\Models\Work;
use Illuminate\Database\Eloquent\Relations\Relation;
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
        // Stable aliases in polymorphic columns (claims, removals, tags).
        Relation::enforceMorphMap([
            'studio' => Studio::class,
            'artist' => Artist::class,
            'work' => Work::class,
            'user' => User::class, // Sanctum tokens
        ]);
    }
}

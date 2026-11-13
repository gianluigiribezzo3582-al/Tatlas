<?php

namespace App\Providers;

use App\Models\Artist;
use App\Models\Studio;
use App\Models\User;
use App\Models\Work;
use App\Services\Osm\OverpassClient;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(OverpassClient::class, fn () => OverpassClient::fromConfig());
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

        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(120)
            ->by($request->user()?->id ?: $request->ip()));
        RateLimiter::for('auth', fn (Request $request) => Limit::perMinute(10)->by($request->ip()));
    }
}

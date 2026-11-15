<?php

namespace App\Providers;

use App\Models\Artist;
use App\Models\Studio;
use App\Models\User;
use App\Models\Work;
use App\Services\Geocoding\Geocoder;
use App\Services\Geocoding\PhotonGeocoder;
use App\Services\Osm\OverpassClient;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Gate;
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
        $this->app->bind(Geocoder::class, fn () => new PhotonGeocoder(
            url: config('geocoding.photon.url'),
            perMinute: config('geocoding.photon.per_minute'),
            userAgent: config('geocoding.photon.user_agent'),
        ));
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
        // Italian transactional emails (the app's primary language).
        VerifyEmail::toMailUsing(fn ($user, string $url) => (new MailMessage)
            ->subject('Conferma la tua email · Tatlas')
            ->greeting('Ciao '.$user->name.',')
            ->line('conferma il tuo indirizzo email per completare la registrazione su Tatlas.')
            ->action('Conferma email', $url)
            ->line('Se non hai creato tu l\'account, ignora questo messaggio.')
            ->salutation('Il team di Tatlas'));
        ResetPassword::toMailUsing(fn ($user, string $token) => (new MailMessage)
            ->subject('Reimposta la password · Tatlas')
            ->greeting('Ciao '.$user->name.',')
            ->line('abbiamo ricevuto una richiesta di reimpostazione della password del tuo account.')
            ->action('Scegli una nuova password', route('password.reset', ['token' => $token, 'email' => $user->getEmailForPasswordReset()]))
            ->line('Il link scade tra '.config('auth.passwords.users.expire').' minuti. Se non sei stato tu, ignora questo messaggio.')
            ->salutation('Il team di Tatlas'));

        Gate::define('admin', fn (User $user) => $user->isAdmin());

        RateLimiter::for('uploads', fn (Request $request) => Limit::perMinute(20)->by($request->user()?->id ?: $request->ip()));
        RateLimiter::for('auth', fn (Request $request) => Limit::perMinute(10)->by($request->ip()));
    }
}

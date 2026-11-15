<?php

namespace App\Http\Controllers\Web;

use App\Enums\StudioSource;
use App\Http\Controllers\Controller;
use App\Http\Resources\V1\WorkResource;
use App\Models\Artist;
use App\Models\Studio;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Shareable, indexable public pages. They mirror the app screens and link into the
 * app through Android App Links (same paths as the app's routes).
 */
class PublicPageController extends Controller
{
    public function artist(string $slug): View
    {
        $artist = Artist::visible()->where('slug', $slug)->firstOrFail();
        $today = now()->toDateString();

        $artist->load([
            'styles',
            'works' => fn ($q) => $q->whereNull('hidden_at')->limit(24),
            'studios' => fn ($q) => $q->visible()
                ->where(fn ($q) => $q->where('artist_studio.ends_at', '>=', $today)->orWhereNull('artist_studio.ends_at'))
                ->orderBy('artist_studio.type', 'desc')->orderBy('artist_studio.starts_at'),
        ]);

        $url = self::artistUrl($artist);
        $residents = $artist->studios->filter(fn ($s) => $s->pivot->type->value === 'resident');

        $jsonLd = array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Person',
            'name' => $artist->name,
            'url' => $url,
            'jobTitle' => 'Tatuatore',
            'description' => $artist->bio ? Str::limit($artist->bio, 300) : null,
            'knowsAbout' => $artist->styles->pluck('name_it')->values()->all() ?: null,
            'knowsLanguage' => $artist->languages ?: null,
            'sameAs' => array_values(array_filter([$artist->instagram_url, $artist->website])) ?: null,
            'image' => $artist->works->first() ? WorkResource::url($artist->works->first()->image_path) : null,
            'worksFor' => $residents->map(fn (Studio $s) => self::studioJsonLd($s, nested: true))->values()->all() ?: null,
        ]);

        return view('public.artist', [
            'artist' => $artist,
            'url' => $url,
            'jsonLd' => $jsonLd,
            'mapPoints' => $artist->studios->map(fn (Studio $s) => [
                'lat' => $s->location->lat, 'lng' => $s->location->lng, 'name' => $s->name,
            ])->unique(fn ($p) => $p['lat'].$p['lng'])->values(),
            'appUrl' => 'tatlas://artist/'.$artist->slug,
            'noindex' => str_starts_with($artist->slug, 'demo-'),
        ]);
    }

    public function studio(string $ref): View|RedirectResponse
    {
        abort_unless(preg_match('/^(\d+)(?:-[a-z0-9-]*)?$/', $ref, $m) === 1, 404);
        $studio = Studio::visible()->findOrFail((int) $m[1]);

        $canonical = self::studioPath($studio);
        if ('/studi/'.$ref !== $canonical) {
            return redirect($canonical, 301);
        }

        $studio->load(['artists' => fn ($q) => $q->visible()
            ->where(fn ($q) => $q->where('artist_studio.ends_at', '>=', now()->toDateString())->orWhereNull('artist_studio.ends_at'))
            ->orderBy('artist_studio.type', 'desc')->orderBy('artist_studio.starts_at')
            ->with('styles'),
        ]);

        return view('public.studio', [
            'studio' => $studio,
            'url' => self::studioUrl($studio),
            'jsonLd' => self::studioJsonLd($studio),
            'appUrl' => 'tatlas://studio/'.$studio->id,
            'isOsm' => $studio->source === StudioSource::Osm,
            'noindex' => str_starts_with($studio->slug, 'demo-'),
        ]);
    }

    public function sitemap(): Response
    {
        $artists = Artist::visible()->where('slug', 'not like', 'demo-%')->get(['slug', 'updated_at']);
        $studios = Studio::visible()->where('slug', 'not like', 'demo-%')->get(['id', 'slug', 'name', 'updated_at']);

        return response()
            ->view('public.sitemap', ['artists' => $artists, 'studios' => $studios])
            ->header('Content-Type', 'application/xml');
    }

    /** Android App Links verification (Digital Asset Links). */
    public function assetLinks(): JsonResponse
    {
        $fingerprints = array_values(array_filter(array_map('trim', explode(',', (string) config('tatlas.android_cert_sha256')))));

        return response()->json($fingerprints === [] ? [] : [[
            'relation' => ['delegate_permission/common.handle_all_urls'],
            'target' => [
                'namespace' => 'android_app',
                'package_name' => config('tatlas.android_package'),
                'sha256_cert_fingerprints' => $fingerprints,
            ],
        ]]);
    }

    public static function base(): string
    {
        return rtrim((string) config('tatlas.public_url'), '/');
    }

    public static function artistUrl(Artist $artist): string
    {
        return self::base().'/artisti/'.$artist->slug;
    }

    public static function studioPath(Studio $studio): string
    {
        return '/studi/'.$studio->id.'-'.Str::slug($studio->name);
    }

    public static function studioUrl(Studio $studio): string
    {
        return self::base().self::studioPath($studio);
    }

    /** @return array<string, mixed> schema.org TattooParlor */
    public static function studioJsonLd(Studio $studio, bool $nested = false): array
    {
        return array_filter([
            '@context' => $nested ? null : 'https://schema.org',
            '@type' => 'TattooParlor',
            'name' => $studio->name,
            'url' => self::studioUrl($studio),
            'telephone' => $studio->phone,
            'email' => $studio->email,
            'openingHours' => $studio->opening_hours,
            'sameAs' => array_values(array_filter([$studio->website, $studio->instagram_url])) ?: null,
            'address' => array_filter([
                '@type' => 'PostalAddress',
                'streetAddress' => trim(($studio->street ?? '').' '.($studio->housenumber ?? '')) ?: null,
                'postalCode' => $studio->postcode,
                'addressLocality' => $studio->city,
                'addressCountry' => $studio->country_code,
            ]),
            'geo' => [
                '@type' => 'GeoCoordinates',
                'latitude' => $studio->location->lat,
                'longitude' => $studio->location->lng,
            ],
        ], fn ($v) => $v !== null);
    }
}

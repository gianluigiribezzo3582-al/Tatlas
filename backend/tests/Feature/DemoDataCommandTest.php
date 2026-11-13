<?php

use App\Models\Artist;
use App\Models\Studio;
use App\Models\Work;
use Database\Seeders\StyleSeeder;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
    $this->seed(StyleSeeder::class);
});

it('creates demo artists with styles, presences and images, then purges them', function () {
    $this->artisan('tatlas:demo-data', ['--artists' => 5])->assertSuccessful();

    expect(Artist::where('slug', 'like', 'demo-%')->count())->toBe(5)
        ->and(Studio::count())->toBe(8)
        ->and(Work::count())->toBeGreaterThanOrEqual(10);

    $artist = Artist::with('styles', 'studios')->first();
    expect($artist->styles)->not->toBeEmpty()
        ->and($artist->studios->pluck('pivot.type.value'))->toContain('resident');
    Storage::disk('public')->assertExists(Work::first()->image_path);

    $this->artisan('tatlas:demo-data', ['--purge' => true])->assertSuccessful();

    expect(Artist::count())->toBe(0)->and(Studio::count())->toBe(0)->and(Work::count())->toBe(0);
});

it('reuses existing studios and never deletes OSM ones on purge', function () {
    $osm = Studio::factory()->fromOsm(1)->create();

    $this->artisan('tatlas:demo-data', ['--artists' => 2])->assertSuccessful();
    $this->artisan('tatlas:demo-data', ['--purge' => true])->assertSuccessful();

    expect(Studio::pluck('id')->all())->toBe([$osm->id]);
});

it('refuses to run in production', function () {
    app()->detectEnvironment(fn () => 'production');

    $this->artisan('tatlas:demo-data')->assertFailed();
});

it('serves work image URLs from the request host', function () {
    $this->artisan('tatlas:demo-data', ['--artists' => 1]);
    $slug = Artist::value('slug');

    $this->getJson("http://192.168.1.20:8000/api/v1/artists/{$slug}")
        ->assertOk()
        ->assertJsonPath('data.works.0.image_url', fn ($url) => str_starts_with($url, 'http://192.168.1.20:8000/storage/works/demo/'));
});

<?php

use App\Models\Artist;
use App\Models\Studio;
use App\Models\Style;
use App\Models\Work;
use Database\Seeders\StyleSeeder;

beforeEach(function () {
    $this->seed(StyleSeeder::class);
    $this->travelTo('2026-12-01');
});

it('finds artists through their studios with the nearest distance', function () {
    $near = Studio::factory()->at(41.1225, 16.8689)->create();
    $far = Studio::factory()->at(41.0603, 16.9879)->create();

    $a = Artist::factory()->create(['name' => 'Anna']);
    $a->studios()->attach($far, ['type' => 'resident']);
    $a->studios()->attach($near, ['type' => 'guest', 'starts_at' => '2026-12-05', 'ends_at' => '2026-12-06']);
    $b = Artist::factory()->create(['name' => 'Bruno']);
    $b->studios()->attach($far, ['type' => 'resident']);
    $gone = Artist::factory()->create(['name' => 'Past guest only']);
    $gone->studios()->attach($near, ['type' => 'guest', 'starts_at' => '2026-10-01', 'ends_at' => '2026-10-03']);

    $response = $this->getJson('/api/v1/artists?lat=41.1225&lng=16.8689&radius=30000')->assertOk();

    expect($response->json('data.*.name'))->toBe(['Anna', 'Bruno'])
        ->and($response->json('data.0.distance_m'))->toBeLessThan(10);
});

it('filters artists by style and by upcoming guest spots', function () {
    $studio = Studio::factory()->at(41.12, 16.87)->create();
    $fine = Artist::factory()->create(['name' => 'Fine']);
    $fine->styles()->attach(Style::where('slug', 'fine-line')->value('id'));
    $fine->studios()->attach($studio, ['type' => 'resident']);
    $guest = Artist::factory()->create(['name' => 'Guest']);
    $guest->studios()->attach($studio, ['type' => 'guest', 'starts_at' => '2026-12-03']);

    $this->getJson('/api/v1/artists?bbox=16.25,40.72,17.53,41.32&style=fine-line')
        ->assertJsonCount(1, 'data')->assertJsonPath('data.0.name', 'Fine');

    $this->getJson('/api/v1/artists?bbox=16.25,40.72,17.53,41.32&guest_upcoming=1')
        ->assertJsonCount(1, 'data')->assertJsonPath('data.0.name', 'Guest');
});

it('shows an artist by slug with styles, works and current studios', function () {
    $studio = Studio::factory()->create(['name' => 'Home']);
    $artist = Artist::factory()->create(['slug' => 'mara-ink', 'name' => 'Mara']);
    $artist->styles()->attach(Style::where('slug', 'watercolor')->value('id'), ['weight' => 0.8]);
    $artist->studios()->attach($studio, ['type' => 'resident']);
    Work::create(['artist_id' => $artist->id, 'image_path' => 'works/1.jpg', 'position' => 0]);

    $this->getJson('/api/v1/artists/mara-ink')
        ->assertOk()
        ->assertJsonPath('data.name', 'Mara')
        ->assertJsonPath('data.styles.0.slug', 'watercolor')
        ->assertJsonPath('data.styles.0.weight', 0.8)
        ->assertJsonPath('data.studios.0.name', 'Home')
        ->assertJsonPath('data.studios.0.presence.type', 'resident')
        ->assertJsonPath('data.works.0.id', 1)
        ->assertJsonPath('data.public_url', 'https://tatlas.app/artisti/mara-ink')
        ->assertJsonMissingPath('data.text_embedding');
});

it('returns 404 for unknown or hidden artists', function () {
    $this->getJson('/api/v1/artists/nobody')->assertNotFound();

    $hidden = Artist::factory()->create(['hidden_at' => now()]);
    $hidden->forceFill(['hidden_at' => now()])->save();
    $this->getJson("/api/v1/artists/{$hidden->slug}")->assertNotFound();
});

it('lists the style taxonomy as a localized tree', function () {
    $response = $this->getJson('/api/v1/styles', ['Accept-Language' => 'en'])->assertOk();

    $blackwork = collect($response->json('data'))->firstWhere('slug', 'blackwork');
    expect($blackwork['name'])->toBe('Blackwork')
        ->and(collect($blackwork['children'])->pluck('slug'))->toContain('dotwork');

    $watercolor = collect($this->getJson('/api/v1/styles', ['Accept-Language' => 'it'])->json('data'))
        ->firstWhere('slug', 'watercolor');
    expect($watercolor['name'])->toBe('Acquerello');
});

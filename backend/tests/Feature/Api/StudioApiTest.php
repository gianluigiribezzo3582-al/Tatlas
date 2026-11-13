<?php

use App\Models\Artist;
use App\Models\Studio;
use App\Models\Style;
use Database\Seeders\StyleSeeder;

// Bari Murattiano ≈ (41.1225, 16.8689); Triggiano ≈ 9 km SE; Milano far away.
beforeEach(function () {
    $this->seed(StyleSeeder::class);
    $this->centre = Studio::factory()->at(41.1225, 16.8689)->create(['name' => 'Centro Ink']);
    $this->triggiano = Studio::factory()->at(41.0603, 16.9879)->create(['name' => 'Triggiano Tattoo']);
    $this->milano = Studio::factory()->at(45.4642, 9.19)->create(['name' => 'Milano Tattoo']);
});

const BARI_BBOX = '16.25,40.72,17.53,41.32';

it('lists studios inside a bbox (west,south,east,north)', function () {
    $this->getJson('/api/v1/studios?bbox='.BARI_BBOX)
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.name', 'Centro Ink')
        ->assertJsonPath('data.0.location.lat', 41.1225)
        ->assertJsonPath('meta.count', 2)
        ->assertJsonPath('meta.attribution', '© OpenStreetMap contributors (ODbL)');
});

it('filters by radius and sorts by distance', function () {
    $response = $this->getJson('/api/v1/studios?lat=41.06&lng=16.98&radius=20000')->assertOk();

    expect($response->json('data.*.name'))->toBe(['Triggiano Tattoo', 'Centro Ink'])
        ->and($response->json('data.0.distance_m'))->toBeLessThan(1000)
        ->and($response->json('data.1.distance_m'))->toBeGreaterThan(8000);

    $this->getJson('/api/v1/studios?lat=41.06&lng=16.98&radius=2000')->assertJsonCount(1, 'data');
});

it('excludes hidden studios', function () {
    $this->triggiano->forceFill(['hidden_at' => now()])->save();

    $this->getJson('/api/v1/studios?bbox='.BARI_BBOX)->assertJsonCount(1, 'data');
});

it('filters by style including sub-styles', function () {
    $artist = Artist::factory()->create();
    $artist->styles()->attach(Style::where('slug', 'dotwork')->value('id'));
    $artist->studios()->attach($this->triggiano, ['type' => 'resident']);

    $this->getJson('/api/v1/studios?bbox='.BARI_BBOX.'&style=blackwork')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Triggiano Tattoo')
        ->assertJsonPath('data.0.artists_count', 1);

    $this->getJson('/api/v1/studios?bbox='.BARI_BBOX.'&style=realism')->assertJsonCount(0, 'data');
});

it('filters studios with upcoming or ongoing guest spots', function () {
    $this->travelTo('2026-12-01');
    $soon = Artist::factory()->create();
    $soon->studios()->attach($this->centre, ['type' => 'guest', 'starts_at' => '2026-12-10', 'ends_at' => '2026-12-12']);
    $past = Artist::factory()->create();
    $past->studios()->attach($this->triggiano, ['type' => 'guest', 'starts_at' => '2026-11-01', 'ends_at' => '2026-11-05']);

    $this->getJson('/api/v1/studios?bbox='.BARI_BBOX.'&guest_upcoming=1')
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Centro Ink')
        ->assertJsonPath('data.0.has_upcoming_guest', true);
});

it('validates area parameters', function (string $query, string $field) {
    $this->getJson('/api/v1/studios?'.$query)->assertUnprocessable()->assertJsonValidationErrors($field);
})->with([
    'no area' => ['', 'bbox'],
    'bad bbox' => ['bbox=1,2,3', 'bbox'],
    'inverted bbox' => ['bbox=17,41,16,40', 'bbox'],
    'radius without origin' => ['radius=1000', 'lat'],
    'radius too large' => ['lat=41&lng=16&radius=500000', 'radius'],
    'unknown style' => ['bbox='.BARI_BBOX.'&style=nope', 'style'],
]);

it('shows a studio with current artists and OSM attribution', function () {
    $this->travelTo('2026-12-01');
    $studio = Studio::factory()->fromOsm(555)->at(41.12, 16.87)->create();
    $resident = Artist::factory()->create(['name' => 'Resident One']);
    $resident->studios()->attach($studio, ['type' => 'resident']);
    $guest = Artist::factory()->create(['name' => 'Guest Two']);
    $guest->studios()->attach($studio, ['type' => 'guest', 'starts_at' => '2026-12-20', 'ends_at' => '2026-12-22']);
    $old = Artist::factory()->create(['name' => 'Old Guest']);
    $old->studios()->attach($studio, ['type' => 'guest', 'starts_at' => '2026-10-01', 'ends_at' => '2026-10-02']);

    $response = $this->getJson("/api/v1/studios/{$studio->id}")->assertOk();

    expect($response->json('data.artists.*.name'))->toBe(['Resident One', 'Guest Two'])
        ->and($response->json('data.artists.1.presence'))->toBe([
            'type' => 'guest', 'starts_at' => '2026-12-20', 'ends_at' => '2026-12-22',
        ])
        ->and($response->json('data.attribution.osm_url'))->toBe('https://www.openstreetmap.org/node/555');
});

it('returns 404 for hidden studios', function () {
    $this->centre->forceFill(['hidden_at' => now()])->save();

    $this->getJson("/api/v1/studios/{$this->centre->id}")->assertNotFound();
});

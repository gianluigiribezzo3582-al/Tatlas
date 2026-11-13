<?php

use App\Models\Studio;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Http::preventStrayRequests();
    config(['osm.overpass_url' => 'https://overpass.test/api/interpreter']);
});

/** Payload served by the fake Overpass; tests can swap it between runs. */
function overpassPayload(?array $set = null): array
{
    static $payload = null;
    if ($set !== null) {
        $payload = $set;
    }

    return $payload ?? [];
}

function fixtureOverpassElements(): array
{
    return json_decode(file_get_contents(base_path('tests/Fixtures/overpass/bari_tattoo_shops.json')), true)['elements'];
}

function fakeOverpass(?array $elements = null): void
{
    overpassPayload(['elements' => $elements ?? fixtureOverpassElements()]);
    Http::fake(['overpass.test/*' => fn () => Http::response(overpassPayload())]);
}

it('imports studios from Overpass and skips unusable elements', function () {
    fakeOverpass();

    $this->artisan('tatlas:import-osm')->assertSuccessful();

    expect(Studio::count())->toBe(4);

    $studio = Studio::where('osm_id', 1000000001)->first();
    expect($studio->source->value)->toBe('osm')
        ->and($studio->slug)->toBe('inchiostro-murattiano')
        ->and($studio->osm_tags['shop'])->toBe('tattoo')
        ->and($studio->osm_synced_at)->not->toBeNull();

    // A second studio with the same name gets a city-qualified slug.
    expect(Studio::where('osm_id', 1000000005)->value('slug'))->toBe('inchiostro-murattiano-bari');
});

it('sends the expected Overpass query with the default bbox and a User-Agent', function () {
    fakeOverpass();
    config(['osm.default_bbox' => '40.72,16.25,41.32,17.53']);

    $this->artisan('tatlas:import-osm')->assertSuccessful();

    Http::assertSent(function (Request $request) {
        return $request->url() === 'https://overpass.test/api/interpreter'
            && str_contains($request['data'], 'nwr["shop"="tattoo"](40.72,16.25,41.32,17.53);')
            && str_contains($request['data'], 'out center tags;')
            && str_starts_with($request->header('User-Agent')[0], 'Tatlas/');
    });
});

it('is idempotent on osm id', function () {
    fakeOverpass();

    $this->artisan('tatlas:import-osm')->assertSuccessful();
    $first = Studio::orderBy('id')->get(['id', 'slug', 'name'])->toArray();

    $this->artisan('tatlas:import-osm')
        ->expectsTable(
            ['created', 'updated', 'unchanged', 'restored', 'skipped', 'missing'],
            [[0, 0, 4, 0, 1, 0]],
        )
        ->assertSuccessful();

    expect(Studio::orderBy('id')->get(['id', 'slug', 'name'])->toArray())->toBe($first);
});

it('updates changed OSM fields but keeps locked ones and the slug', function () {
    fakeOverpass();
    $this->artisan('tatlas:import-osm');

    $studio = Studio::where('osm_id', 1000000001)->first();
    $studio->update(['phone' => '+39 333 1234567', 'locked_fields' => ['phone']]);

    $elements = fixtureOverpassElements();
    $elements[0]['tags']['name'] = 'Inchiostro Murattiano Studio';
    $elements[0]['tags']['phone'] = '+39 080 999 9999';
    $elements[0]['lat'] = 41.1230;
    fakeOverpass($elements);

    $this->artisan('tatlas:import-osm')->assertSuccessful();

    $studio->refresh();
    expect($studio->name)->toBe('Inchiostro Murattiano Studio')
        ->and($studio->phone)->toBe('+39 333 1234567')
        ->and($studio->location->lat)->toEqualWithDelta(41.1230, 1e-7)
        ->and($studio->slug)->toBe('inchiostro-murattiano');
});

it('flags studios that disappeared from OSM inside the bbox and restores them when they return', function () {
    fakeOverpass();
    $this->artisan('tatlas:import-osm');

    $elements = fixtureOverpassElements();
    fakeOverpass(array_slice($elements, 1));
    $this->artisan('tatlas:import-osm')->assertSuccessful();

    $gone = Studio::where('osm_id', 1000000001)->first();
    expect($gone)->not->toBeNull()
        ->and($gone->osm_missing_since)->not->toBeNull();

    fakeOverpass($elements);
    $this->artisan('tatlas:import-osm')->assertSuccessful();

    expect($gone->fresh()->osm_missing_since)->toBeNull();
});

it('does not flag studios outside the requested bbox', function () {
    $milan = Studio::factory()->fromOsm(42)->at(45.4642, 9.19)->create();
    fakeOverpass();

    $this->artisan('tatlas:import-osm', ['--bbox' => '40.72,16.25,41.32,17.53'])->assertSuccessful();

    expect($milan->fresh()->osm_missing_since)->toBeNull();
});

it('leaves user-created studios untouched', function () {
    $own = Studio::factory()->at(41.12, 16.87)->create();
    fakeOverpass([]);

    $this->artisan('tatlas:import-osm')->assertSuccessful();

    expect($own->fresh()->osm_missing_since)->toBeNull();
});

it('writes nothing on dry run', function () {
    fakeOverpass();

    $this->artisan('tatlas:import-osm', ['--dry-run' => true])
        ->expectsOutputToContain('Dry run')
        ->assertSuccessful();

    expect(Studio::count())->toBe(0);
});

it('rejects an invalid bbox', function () {
    $this->artisan('tatlas:import-osm', ['--bbox' => '1,2,3'])->assertExitCode(2);
});

it('fails cleanly when Overpass errors', function () {
    Http::fake(['overpass.test/*' => Http::response('bad', 400)]);

    $this->artisan('tatlas:import-osm')
        ->expectsOutputToContain('Overpass request failed: HTTP 400')
        ->assertFailed();

    expect(Studio::count())->toBe(0);
});

it('treats an Overpass runtime remark as a failure', function () {
    Http::fake(['overpass.test/*' => Http::response([
        'elements' => [],
        'remark' => 'runtime error: Query timed out in "query" at line 2 after 91 seconds.',
    ])]);

    $this->artisan('tatlas:import-osm')->assertFailed();
});

it('prints the ODbL attribution', function () {
    fakeOverpass();

    $this->artisan('tatlas:import-osm')
        ->expectsOutputToContain('OpenStreetMap contributors, ODbL')
        ->assertSuccessful();
});

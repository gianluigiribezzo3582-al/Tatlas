<?php

use App\Services\Osm\OsmStudioMapper;

function fixtureElements(): array
{
    return json_decode(file_get_contents(base_path('tests/Fixtures/overpass/bari_tattoo_shops.json')), true)['elements'];
}

it('maps a fully tagged node', function () {
    $studio = (new OsmStudioMapper)->map(fixtureElements()[0]);

    expect($studio)
        ->osm_type->toBe('node')
        ->osm_id->toBe(1000000001)
        ->name->toBe('Inchiostro Murattiano')
        ->street->toBe('Via Sparano da Bari')
        ->housenumber->toBe('12')
        ->postcode->toBe('70121')
        ->city->toBe('Bari')
        ->country_code->toBe('IT')
        ->opening_hours->toBe('Tu-Sa 10:00-19:00')
        ->phone->toBe('+39 080 000 0001')
        ->website->toBe('https://inchiostro-murattiano.example')
        ->instagram_url->toBe('https://www.instagram.com/inchiostro.murattiano')
        ->and($studio['location']->lat)->toBe(41.1225)
        ->and($studio['location']->lng)->toBe(16.8689);
});

it('uses the center of ways and normalizes whitespace and contact:* tags', function () {
    $studio = (new OsmStudioMapper)->map(fixtureElements()[1]);

    expect($studio)
        ->osm_type->toBe('way')
        ->name->toBe('Bari Vecchia Ink')
        ->email->toBe('info@bvink.example')
        ->instagram_url->toBe('https://www.instagram.com/bv_ink')
        ->and($studio['location']->lat)->toBe(41.1284);
});

it('falls back to url and contact:phone', function () {
    $studio = (new OsmStudioMapper)->map(fixtureElements()[2]);

    expect($studio)
        ->phone->toBe('+39 080 000 0003')
        ->website->toBe('http://agoechina.example/');
});

it('skips elements without a name or coordinates', function () {
    $mapper = new OsmStudioMapper;

    expect($mapper->map(fixtureElements()[3]))->toBeNull()
        ->and($mapper->map(['type' => 'way', 'id' => 1, 'tags' => ['name' => 'X']]))->toBeNull()
        ->and($mapper->map(['type' => 'area', 'id' => 1, 'lat' => 1, 'lon' => 1, 'tags' => ['name' => 'X']]))->toBeNull();
});

it('drops invalid instagram handles', function () {
    $studio = (new OsmStudioMapper)->map([
        'type' => 'node', 'id' => 9, 'lat' => 41, 'lon' => 16,
        'tags' => ['name' => 'X', 'instagram' => 'not a handle!'],
    ]);

    expect($studio['instagram_url'])->toBeNull();
});

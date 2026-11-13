<?php

use App\Services\Geocoding\Geocoder;
use App\Services\Geocoding\GeocodingUnavailable;
use App\Support\Geo\Point;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Http::preventStrayRequests();
    config(['geocoding.photon.url' => 'https://photon.test', 'geocoding.photon.per_minute' => 2]);
    $this->photonPayload = ['features' => [[
        'geometry' => ['type' => 'Point', 'coordinates' => [16.8689, 41.1225]],
        'properties' => [
            'name' => 'Via Sparano', 'street' => 'Via Sparano da Bari', 'housenumber' => '12',
            'postcode' => '70121', 'city' => 'Bari', 'countrycode' => 'it',
        ],
    ]]];
});

it('geocodes an address with a location bias', function () {
    Http::fake(['photon.test/*' => Http::response($this->photonPayload)]);

    $results = app(Geocoder::class)->search('via sparano 12 bari', new Point(41.12, 16.87));

    expect($results)->toHaveCount(1)
        ->and($results[0]->label)->toBe('Via Sparano da Bari 12, 70121 Bari')
        ->and($results[0]->countryCode)->toBe('IT')
        ->and($results[0]->point->lat)->toBe(41.1225);

    Http::assertSent(fn ($r) => $r['q'] === 'via sparano 12 bari'
        && (float) $r['lat'] === 41.12 && $r['lang'] === 'it');
});

it('caches identical queries', function () {
    Http::fake(['photon.test/*' => Http::response($this->photonPayload)]);
    $geocoder = app(Geocoder::class);

    $geocoder->search('Via Sparano  12');
    $geocoder->search('Via Sparano 12');

    Http::assertSentCount(1);
});

it('stops calling Photon above the rate limit', function () {
    Http::fake(['photon.test/*' => Http::response(['features' => []])]);
    $geocoder = app(Geocoder::class);

    $geocoder->search('a');
    $geocoder->search('b');
    $geocoder->search('c');
})->throws(GeocodingUnavailable::class, 'rate limit');

it('reports upstream errors as unavailable', function () {
    Http::fake(['photon.test/*' => Http::response('', 503)]);

    app(Geocoder::class)->search('via roma');
})->throws(GeocodingUnavailable::class);

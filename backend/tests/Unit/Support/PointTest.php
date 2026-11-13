<?php

use App\Support\Geo\Point;

it('renders EWKT with lng first', function () {
    expect((new Point(lat: 41.1171, lng: 16.8719))->toEwkt())
        ->toBe('SRID=4326;POINT(16.8719000 41.1171000)');
});

it('parses little-endian EWKB with SRID as returned by PostGIS', function () {
    // SELECT 'SRID=4326;POINT(16.8719 41.1171)'::geography
    $point = Point::fromWkbHex('0101000020E6100000E561A1D634DF3040492EFF21FD8E4440');

    expect($point->lng)->toEqualWithDelta(16.8719, 1e-9)
        ->and($point->lat)->toEqualWithDelta(41.1171, 1e-9);
});

it('rejects out of range coordinates', function () {
    new Point(lat: 91, lng: 0);
})->throws(InvalidArgumentException::class);

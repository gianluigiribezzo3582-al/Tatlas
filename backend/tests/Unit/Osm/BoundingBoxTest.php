<?php

use App\Services\Osm\BoundingBox;

it('parses south,west,north,east', function () {
    $bbox = BoundingBox::fromString(' 40.72, 16.25,41.32 ,17.53');

    expect($bbox->toOverpass())->toBe('40.72,16.25,41.32,17.53');
});

it('rejects malformed or inverted boxes', function (string $value) {
    BoundingBox::fromString($value);
})->with([
    'too few parts' => '1,2,3',
    'not numeric' => 'a,b,c,d',
    'south above north' => '41.3,16.2,40.7,17.5',
    'west above east' => '40.7,17.5,41.3,16.2',
    'out of range' => '-95,0,10,10',
])->throws(InvalidArgumentException::class);

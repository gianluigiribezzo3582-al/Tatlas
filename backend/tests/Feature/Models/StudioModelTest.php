<?php

use App\Enums\PresenceType;
use App\Models\Artist;
use App\Models\Studio;
use App\Support\Geo\Point;
use Illuminate\Database\QueryException;

it('persists and reads back a geography point', function () {
    $studio = Studio::factory()->at(41.1171, 16.8719)->create();

    $fresh = $studio->fresh();

    expect($fresh->location)->toBeInstanceOf(Point::class)
        ->and($fresh->location->lat)->toEqualWithDelta(41.1171, 1e-7)
        ->and($fresh->location->lng)->toEqualWithDelta(16.8719, 1e-7);
});

it('links artists to studios as resident or guest', function () {
    $studio = Studio::factory()->create();
    $artist = Artist::factory()->create();

    $artist->studios()->attach($studio, ['type' => PresenceType::Resident->value]);
    $artist->studios()->attach($studio, [
        'type' => PresenceType::Guest->value,
        'starts_at' => '2026-12-01',
        'ends_at' => '2026-12-05',
    ]);

    expect($studio->residents()->count())->toBe(1)
        ->and($artist->studios()->count())->toBe(2)
        ->and($artist->studios->pluck('pivot.type')->map->value->sort()->values()->all())
        ->toBe(['guest', 'resident']);
});

it('requires a start date for guest spots', function () {
    $studio = Studio::factory()->create();
    $artist = Artist::factory()->create();

    $artist->studios()->attach($studio, ['type' => PresenceType::Guest->value]);
})->throws(QueryException::class);

it('rejects guest spots that end before they start', function () {
    $studio = Studio::factory()->create();
    $artist = Artist::factory()->create();

    $artist->studios()->attach($studio, [
        'type' => PresenceType::Guest->value,
        'starts_at' => '2026-12-05',
        'ends_at' => '2026-12-01',
    ]);
})->throws(QueryException::class);

it('enforces one studio per OSM element', function () {
    Studio::factory()->fromOsm(123)->create();
    Studio::factory()->fromOsm(123)->create();
})->throws(QueryException::class);

it('allows the same osm id for different element types', function () {
    Studio::factory()->fromOsm(123, 'node')->create();
    Studio::factory()->fromOsm(123, 'way')->create();

    expect(Studio::count())->toBe(2);
});

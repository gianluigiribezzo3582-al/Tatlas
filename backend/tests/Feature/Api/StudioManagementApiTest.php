<?php

use App\Models\ClaimRequest;
use App\Models\Studio;
use App\Models\User;
use App\Services\Geocoding\Geocoder;
use App\Services\Geocoding\GeocodeResult;
use App\Services\Geocoding\GeocodingUnavailable;
use App\Support\Geo\Point;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->user = User::factory()->create();
    Sanctum::actingAs($this->user);
});

function fakeGeocoder(?GeocodeResult $result = null, bool $unavailable = false): void
{
    app()->instance(Geocoder::class, new class($result, $unavailable) implements Geocoder
    {
        public function __construct(private ?GeocodeResult $result, private bool $unavailable) {}

        public function search(string $query, ?Point $near = null, int $limit = 5): array
        {
            if ($this->unavailable) {
                throw new GeocodingUnavailable('down');
            }

            return $this->result ? [$this->result] : [];
        }
    });
}

it('creates a studio geocoding its address, owned by the creator', function () {
    fakeGeocoder(new GeocodeResult(new Point(41.1225, 16.8689), 'Via Sparano 12, Bari'));

    $this->postJson('/api/v1/studios', [
        'name' => 'Nuovo Studio', 'street' => 'Via Sparano', 'housenumber' => '12', 'city' => 'Bari',
    ])->assertCreated()
        ->assertJsonPath('data.location.lat', 41.1225)
        ->assertJsonPath('data.source', 'user');

    $studio = Studio::firstOrFail();
    expect($studio->claimed_by_user_id)->toBe($this->user->id)
        ->and($studio->created_by_user_id)->toBe($this->user->id);
});

it('accepts an explicit pin and reports geocoding problems', function () {
    fakeGeocoder(null);
    $this->postJson('/api/v1/studios', ['name' => 'Pin', 'city' => 'Bari', 'lat' => 41.1, 'lng' => 16.8])
        ->assertCreated();

    $this->postJson('/api/v1/studios', ['name' => 'Lost', 'city' => 'Nowhere', 'street' => 'Via X'])
        ->assertUnprocessable()->assertJsonPath('code', 'address_not_found');

    fakeGeocoder(unavailable: true);
    $this->postJson('/api/v1/studios', ['name' => 'Down', 'city' => 'Bari'])
        ->assertStatus(503)->assertJsonPath('code', 'geocoding_unavailable');
});

it('lets only the owner or an admin edit a studio, locking edited OSM fields', function () {
    $studio = Studio::factory()->fromOsm(10)->create(['phone' => '1']);

    $this->patchJson("/api/v1/studios/{$studio->id}", ['phone' => '2'])->assertForbidden();

    $studio->forceFill(['claimed_by_user_id' => $this->user->id])->save();
    $this->patchJson("/api/v1/studios/{$studio->id}", ['phone' => '+39 080 1', 'lat' => 41.2, 'lng' => 16.9])
        ->assertOk()->assertJsonPath('data.phone', '+39 080 1');

    expect($studio->fresh()->locked_fields)->toContain('phone', 'location');

    $admin = User::factory()->create();
    $admin->forceFill(['role' => 'admin'])->save();
    Sanctum::actingAs($admin);
    $this->patchJson("/api/v1/studios/{$studio->id}", ['name' => 'Renamed'])->assertOk();
});

it('searches studios by name or city', function () {
    Studio::factory()->at(41.12, 16.87)->create(['name' => 'Inchiostro Nero', 'city' => 'Bari']);
    Studio::factory()->at(41.2, 16.6)->create(['name' => 'Altro', 'city' => 'Molfetta']);

    $this->getJson('/api/v1/studios/search?q=inchiostro')->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Inchiostro Nero');
    $this->getJson('/api/v1/studios/search?q=molf&lat=41.1&lng=16.8')->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.distance_m', fn ($d) => $d > 10000);
    $this->getJson('/api/v1/studios/search?q=a%')->assertJsonCount(0, 'data');
});

it('files a claim once per studio and lists it', function () {
    $studio = Studio::factory()->fromOsm(5)->create();

    $this->postJson("/api/v1/studios/{$studio->id}/claim", [
        'message' => 'Sono il titolare', 'evidence_url' => 'https://www.instagram.com/studio',
    ])->assertCreated()->assertJsonPath('data.status', 'pending');
    $this->postJson("/api/v1/studios/{$studio->id}/claim")->assertConflict();

    $this->getJson('/api/v1/me/claims')->assertJsonPath('data.0.status', 'pending')
        ->assertJsonPath('data.0.type', 'studio');
    expect(ClaimRequest::count())->toBe(1);
});

it('refuses claims on studios already managed by someone else', function () {
    $studio = Studio::factory()->create();
    $studio->forceFill(['claimed_by_user_id' => User::factory()->create()->id])->save();

    $this->postJson("/api/v1/studios/{$studio->id}/claim")->assertConflict();
});

it('accepts GDPR removal requests without an account', function () {
    $this->app['auth']->forgetGuards();
    $studio = Studio::factory()->create();

    $this->postJson('/api/v1/removal-requests', [
        'requester_name' => 'Mario Rossi',
        'requester_email' => 'mario@example.com',
        'subject_type' => 'studio',
        'subject_id' => $studio->id,
        'reason' => 'Lo studio è chiuso da anni, rimuovetelo per favore.',
    ])->assertCreated();

    $this->postJson('/api/v1/removal-requests', [
        'requester_name' => 'X', 'requester_email' => 'x@example.com',
        'subject_type' => 'artist', 'subject_id' => 999, 'reason' => 'Non esiste questo profilo.',
    ])->assertUnprocessable();
});

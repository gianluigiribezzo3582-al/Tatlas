<?php

use App\Models\Artist;
use App\Models\Studio;
use App\Models\User;
use Database\Seeders\StyleSeeder;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->seed(StyleSeeder::class);
    $this->user = User::factory()->create();
    Sanctum::actingAs($this->user);
});

it('creates the artist profile, promotes the user and keeps it private until verified', function () {
    $this->postJson('/api/v1/me/artist', [
        'name' => 'Mara Ink',
        'bio' => 'Fine line e lettering.',
        'instagram_url' => 'https://www.instagram.com/mara.ink',
        'languages' => ['it', 'en'],
        'styles' => ['fine-line', 'lettering'],
        'price_range' => 2,
    ])->assertCreated()
        ->assertJsonPath('data.slug', 'mara-ink')
        ->assertJsonPath('data.styles.0.slug', 'fine-line')
        ->assertJsonPath('meta.is_public', false)
        ->assertJsonPath('meta.pending_verification', true);

    expect($this->user->fresh()->role->value)->toBe('artist');

    $this->getJson('/api/v1/artists/mara-ink')->assertNotFound();
    $this->getJson('/api/v1/me/artist')->assertOk()->assertJsonPath('data.name', 'Mara Ink');
});

it('allows only one profile per user', function () {
    $this->postJson('/api/v1/me/artist', ['name' => 'One'])->assertCreated();
    $this->postJson('/api/v1/me/artist', ['name' => 'Two'])->assertConflict();
});

it('validates profile fields', function () {
    $this->postJson('/api/v1/me/artist', [
        'name' => 'X',
        'instagram_url' => 'https://evil.example/x',
        'styles' => ['nope'],
        'price_range' => 9,
    ])->assertUnprocessable()->assertJsonValidationErrors(['name', 'instagram_url', 'styles.0', 'price_range']);
});

it('updates the profile and replaces styles', function () {
    $this->postJson('/api/v1/me/artist', ['name' => 'Mara', 'styles' => ['fine-line']]);

    $this->patchJson('/api/v1/me/artist', ['bio' => 'Nuova bio', 'styles' => ['dotwork']])
        ->assertOk()
        ->assertJsonPath('data.bio', 'Nuova bio')
        ->assertJsonCount(1, 'data.styles')
        ->assertJsonPath('data.styles.0.slug', 'dotwork');
});

it('returns null when the user has no profile', function () {
    $this->getJson('/api/v1/me/artist')->assertOk()->assertJsonPath('data', null);
});

it('manages resident studios and guest spots', function () {
    $this->travelTo('2026-12-01');
    $this->postJson('/api/v1/me/artist', ['name' => 'Mara']);
    $home = Studio::factory()->create();
    $away = Studio::factory()->create();

    $this->postJson('/api/v1/me/presences', ['studio_id' => $home->id, 'type' => 'resident'])->assertCreated();
    $this->postJson('/api/v1/me/presences', ['studio_id' => $home->id, 'type' => 'resident'])->assertConflict();

    $this->postJson('/api/v1/me/presences', ['studio_id' => $away->id, 'type' => 'guest'])
        ->assertUnprocessable()->assertJsonValidationErrors('starts_at');
    $this->postJson('/api/v1/me/presences', [
        'studio_id' => $away->id, 'type' => 'guest', 'starts_at' => '2026-11-20',
    ])->assertUnprocessable();

    $response = $this->postJson('/api/v1/me/presences', [
        'studio_id' => $away->id, 'type' => 'guest', 'starts_at' => '2026-12-10', 'ends_at' => '2026-12-12',
    ])->assertCreated();

    expect($response->json('data'))->toHaveCount(2);
    $guestId = collect($response->json('data'))->firstWhere('type', 'guest')['presence_id'];

    $this->getJson('/api/v1/me/presences')->assertOk()->assertJsonPath('data.0.type', 'resident')
        ->assertJsonPath('data.1.starts_at', '2026-12-10');

    $this->deleteJson("/api/v1/me/presences/{$guestId}")->assertOk()->assertJsonCount(1, 'data');
});

it('cannot delete presences of other artists', function () {
    $this->postJson('/api/v1/me/artist', ['name' => 'Mara']);
    $other = Artist::factory()->create();
    $other->studios()->attach(Studio::factory()->create(), ['type' => 'resident']);
    $presenceId = $other->studios()->first()->pivot->id;

    $this->deleteJson("/api/v1/me/presences/{$presenceId}")->assertNotFound();
});

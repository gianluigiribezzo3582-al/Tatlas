<?php

use App\Models\Artist;
use App\Models\ClaimRequest;
use App\Models\Studio;
use App\Models\User;
use Database\Seeders\StyleSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\PersonalAccessToken;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    Storage::fake('public');
    $this->seed(StyleSeeder::class);
    $this->user = User::factory()->create(['password' => 'secret-pass']);
    Sanctum::actingAs($this->user);
});

it('exports the user data', function () {
    $this->postJson('/api/v1/me/artist', ['name' => 'Mara', 'styles' => ['dotwork']]);

    $this->getJson('/api/v1/me/export')->assertOk()
        ->assertJsonPath('account.email', $this->user->email)
        ->assertJsonPath('artist_profile.name', 'Mara')
        ->assertJsonPath('artist_profile.styles', ['dotwork'])
        ->assertJsonMissingPath('account.password');
});

it('deletes the account with profile, portfolio files, claims and tokens', function () {
    $this->postJson('/api/v1/me/artist', ['name' => 'Mara']);
    $this->postJson('/api/v1/me/works', ['image' => UploadedFile::fake()->image('x.jpg', 600, 600)]);
    $managed = Studio::factory()->create();
    $managed->forceFill(['claimed_by_user_id' => $this->user->id])->save();
    $this->postJson('/api/v1/me/presences', ['studio_id' => $managed->id, 'type' => 'resident']);
    ClaimRequest::create(['user_id' => $this->user->id, 'claimable_type' => 'studio', 'claimable_id' => Studio::factory()->create()->id]);
    $this->user->createToken('phone');
    $files = Storage::disk('public')->allFiles();
    expect($files)->toHaveCount(2);

    $this->deleteJson('/api/v1/me', ['password' => 'wrong'])->assertUnprocessable();
    $this->deleteJson('/api/v1/me', ['password' => 'secret-pass'])->assertNoContent();

    expect(User::find($this->user->id))->toBeNull()
        ->and(Artist::count())->toBe(0)
        ->and(ClaimRequest::count())->toBe(0)
        ->and($managed->fresh())->not->toBeNull()
        ->and($managed->fresh()->claimed_by_user_id)->toBeNull()
        ->and(Storage::disk('public')->allFiles())->toBe([])
        ->and(PersonalAccessToken::count())->toBe(0);
});

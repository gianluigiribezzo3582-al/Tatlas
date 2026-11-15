<?php

use App\Livewire\Admin\Dashboard;
use App\Models\Artist;
use App\Models\ClaimRequest;
use App\Models\RemovalRequest;
use App\Models\Studio;
use App\Models\User;
use Livewire\Livewire;

function admin(): User
{
    $user = User::factory()->create(['email' => 'admin@example.com', 'password' => 'secret-pass']);
    $user->forceFill(['role' => 'admin'])->save();

    return $user;
}

it('redirects guests to the login page and refuses non-admins', function () {
    $this->get('/admin')->assertRedirect('/admin/login');

    $this->actingAs(User::factory()->create())->get('/admin')->assertForbidden();
});

it('logs admins in and keeps other users out', function () {
    admin();
    User::factory()->create(['email' => 'user@example.com', 'password' => 'secret-pass']);

    $this->post('/admin/login', ['email' => 'user@example.com', 'password' => 'secret-pass'])
        ->assertSessionHasErrors('email');
    $this->assertGuest();

    $this->post('/admin/login', ['email' => 'ADMIN@example.com', 'password' => 'secret-pass'])
        ->assertRedirect('/admin');
    $this->get('/admin')->assertOk()->assertSee('Artisti da verificare');
});

it('verifies or hides pending artist profiles', function () {
    $pending = Artist::factory()->unverified()->create(['name' => 'Nuova Artista']);
    $spam = Artist::factory()->unverified()->create(['name' => 'Spam']);

    Livewire::actingAs(admin())->test(Dashboard::class)
        ->assertSee('Nuova Artista')
        ->call('verifyArtist', $pending->id)
        ->call('hideArtist', $spam->id)
        ->assertDontSee('Nuova Artista');

    expect($pending->fresh()->is_verified)->toBeTrue()
        ->and($pending->fresh()->isPubliclyVisible())->toBeTrue()
        ->and($spam->fresh()->hidden_at)->not->toBeNull();
});

it('approves a studio claim, assigns ownership and closes competing claims', function () {
    $studio = Studio::factory()->fromOsm(1)->create(['name' => 'Studio Centrale']);
    $winner = User::factory()->create();
    $other = User::factory()->create();
    $claim = ClaimRequest::create(['user_id' => $winner->id, 'claimable_type' => 'studio', 'claimable_id' => $studio->id]);
    $competing = ClaimRequest::create(['user_id' => $other->id, 'claimable_type' => 'studio', 'claimable_id' => $studio->id]);

    Livewire::actingAs(admin())->test(Dashboard::class, ['tab' => 'claims'])
        ->set('tab', 'claims')
        ->assertSee('Studio Centrale')
        ->call('approveClaim', $claim->id);

    expect($studio->fresh()->claimed_by_user_id)->toBe($winner->id)
        ->and($claim->fresh()->status->value)->toBe('approved')
        ->and($competing->fresh()->status->value)->toBe('rejected')
        ->and($winner->fresh()->role->value)->toBe('artist');
});

it('requires a reason to reject a claim', function () {
    $claim = ClaimRequest::create([
        'user_id' => User::factory()->create()->id,
        'claimable_type' => 'studio',
        'claimable_id' => Studio::factory()->create()->id,
    ]);

    $component = Livewire::actingAs(admin())->test(Dashboard::class)->set('tab', 'claims')
        ->call('rejectClaim', $claim->id)
        ->assertHasErrors("reasons.{$claim->id}");
    expect($claim->fresh()->status->value)->toBe('pending');

    $component->set("reasons.{$claim->id}", 'Nessuna prova di titolarità')->call('rejectClaim', $claim->id);
    expect($claim->fresh()->status->value)->toBe('rejected')
        ->and($claim->fresh()->rejection_reason)->toBe('Nessuna prova di titolarità');
});

it('handles removal requests by hiding the subject', function () {
    $studio = Studio::factory()->create();
    $accept = RemovalRequest::create([
        'requester_name' => 'A', 'requester_email' => 'a@example.com',
        'subject_type' => 'studio', 'subject_id' => $studio->id, 'reason' => 'Chiuso da tempo.',
    ]);
    $reject = RemovalRequest::create([
        'requester_name' => 'B', 'requester_email' => 'b@example.com', 'reason' => 'Richiesta generica.',
    ]);

    Livewire::actingAs(admin())->test(Dashboard::class)->set('tab', 'removals')
        ->call('acceptRemoval', $accept->id)
        ->call('rejectRemoval', $reject->id);

    expect($studio->fresh()->hidden_at)->not->toBeNull()
        ->and($accept->fresh()->status->value)->toBe('done')
        ->and($reject->fresh()->status->value)->toBe('rejected');
    $this->getJson("/api/v1/studios/{$studio->id}")->assertNotFound();
});

it('blocks Livewire actions for non-admins', function () {
    Livewire::actingAs(User::factory()->create())->test(Dashboard::class)->assertForbidden();
});

it('creates or promotes admins from the console', function () {
    $this->artisan('tatlas:make-admin', ['email' => 'Boss@Example.com', '--password' => 'secret-pass'])->assertSuccessful();
    expect(User::where('email', 'boss@example.com')->first()->isAdmin())->toBeTrue();

    $user = User::factory()->create();
    $this->artisan('tatlas:make-admin', ['email' => $user->email])->assertSuccessful();
    expect($user->fresh()->isAdmin())->toBeTrue();
});

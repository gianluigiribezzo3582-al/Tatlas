<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Laravel\Sanctum\Sanctum;

beforeEach(fn () => Notification::fake());

it('sends a verification email on registration and verifies through the signed link', function () {
    $this->postJson('/api/v1/auth/register', [
        'name' => 'Lia', 'email' => 'lia@example.com', 'password' => 'secret-pass',
        'password_confirmation' => 'secret-pass', 'device_name' => 'd', 'accept_privacy' => true,
    ])->assertCreated()->assertJsonPath('user.email_verified', false);

    $user = User::where('email', 'lia@example.com')->first();
    $url = null;
    Notification::assertSentTo($user, VerifyEmail::class, function (VerifyEmail $n) use ($user, &$url) {
        $url = $n->toMail($user)->actionUrl;

        return true;
    });
    expect($url)->toContain('/verify-email/');

    $this->get($url)->assertRedirect('/account/email');
    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
    $this->get('/account/email')->assertOk()->assertSee('Email confermata');
});

it('rejects tampered verification links', function () {
    $user = User::factory()->unverified()->create();
    $url = URL::temporarySignedRoute('verification.verify', now()->addHour(), ['id' => $user->id, 'hash' => sha1('other@example.com')]);

    $this->get($url)->assertForbidden();
    $this->get("/verify-email/{$user->id}/".sha1($user->email))->assertForbidden(); // unsigned
    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
});

it('resends the verification email on request', function () {
    $user = User::factory()->unverified()->create();
    Sanctum::actingAs($user);

    $this->postJson('/api/v1/auth/email/resend')->assertOk();
    Notification::assertSentTo($user, VerifyEmail::class);
});

it('answers forgot-password the same way for known and unknown emails', function () {
    $user = User::factory()->create(['email' => 'a@example.com']);

    $known = $this->postJson('/api/v1/auth/forgot-password', ['email' => 'A@example.com'])->assertOk()->json();
    $unknown = $this->postJson('/api/v1/auth/forgot-password', ['email' => 'nobody@example.com'])->assertOk()->json();

    expect($known)->toBe($unknown);
    Notification::assertSentTo($user, ResetPassword::class);
    Notification::assertSentTimes(ResetPassword::class, 1);
});

it('resets the password from the web page and revokes all tokens', function () {
    $user = User::factory()->create(['email' => 'a@example.com', 'password' => 'old-password']);
    $user->createToken('phone');
    $this->postJson('/api/v1/auth/forgot-password', ['email' => 'a@example.com']);

    $token = null;
    Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $n) use (&$token, $user) {
        $token = $n->token;
        expect($n->toMail($user)->actionUrl)->toContain('/reset-password/'.$token);

        return true;
    });

    $this->get("/reset-password/{$token}?email=a@example.com")->assertOk()->assertSee('Imposta una nuova password');

    $this->post('/reset-password', [
        'token' => 'wrong', 'email' => 'a@example.com', 'password' => 'new-password', 'password_confirmation' => 'new-password',
    ])->assertSessionHasErrors('email');

    $this->post('/reset-password', [
        'token' => $token, 'email' => 'a@example.com', 'password' => 'new-password', 'password_confirmation' => 'new-password',
    ])->assertRedirect('/account/password');

    expect(Hash::check('new-password', $user->fresh()->password))->toBeTrue()
        ->and($user->tokens()->count())->toBe(0);
});

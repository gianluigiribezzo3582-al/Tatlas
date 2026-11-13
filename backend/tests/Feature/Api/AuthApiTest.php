<?php

use App\Models\User;

it('registers a user and returns a token', function () {
    $response = $this->postJson('/api/v1/auth/register', [
        'name' => 'Lia',
        'email' => 'Lia@Example.com',
        'password' => 'secret-pass',
        'password_confirmation' => 'secret-pass',
        'role' => 'artist',
        'device_name' => 'pixel',
        'accept_privacy' => true,
    ])->assertCreated();

    expect($response->json('token'))->toBeString()
        ->and($response->json('user.email'))->toBe('lia@example.com')
        ->and($response->json('user.role'))->toBe('artist');

    $this->withToken($response->json('token'))->getJson('/api/v1/me')
        ->assertOk()->assertJsonPath('user.name', 'Lia');
});

it('requires privacy consent and refuses admin self-registration', function () {
    $this->postJson('/api/v1/auth/register', [
        'name' => 'X', 'email' => 'x@example.com', 'password' => 'secret-pass',
        'password_confirmation' => 'secret-pass', 'device_name' => 'd', 'role' => 'admin',
    ])->assertUnprocessable()->assertJsonValidationErrors(['accept_privacy', 'role']);
});

it('logs in and out', function () {
    User::factory()->create(['email' => 'a@example.com', 'password' => 'secret-pass']);

    $this->postJson('/api/v1/auth/login', ['email' => 'a@example.com', 'password' => 'wrong', 'device_name' => 'd'])
        ->assertUnprocessable();

    $token = $this->postJson('/api/v1/auth/login', ['email' => 'A@example.com', 'password' => 'secret-pass', 'device_name' => 'd'])
        ->assertOk()->json('token');

    $this->withToken($token)->postJson('/api/v1/auth/logout')->assertNoContent();

    $this->app['auth']->forgetGuards();
    $this->withToken($token)->getJson('/api/v1/me')->assertUnauthorized();
});

it('rejects unauthenticated access to me', function () {
    $this->getJson('/api/v1/me')->assertUnauthorized();
});

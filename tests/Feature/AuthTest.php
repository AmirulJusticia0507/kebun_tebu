<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

it('redirects guests from protected pages to login', function () {
    foreach (['/map', '/reports', '/reports/create', '/notifications'] as $path) {
        $this->get($path)->assertRedirect('/login');
    }

    $this->get('/api/blocks/geojson')->assertUnauthorized();
});

it('user can login with valid credentials', function () {
    $user = makeFieldOfficer();

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertRedirect('/dashboard');
    $this->assertAuthenticated();
});

it('user cannot login with invalid credentials', function () {
    $user = makeFieldOfficer();

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $response->assertSessionHasErrors('email');
    $this->assertGuest();
});

it('login is throttled after repeated failures', function () {
    $user = makeFieldOfficer();

    foreach (range(1, 5) as $attempt) {
        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('email');
    }

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ])->assertStatus(429);
});

it('user can logout and session is invalidated', function () {
    $user = makeFieldOfficer();

    $this->actingAs($user)
        ->post('/logout')
        ->assertRedirect('/');

    $this->assertGuest();
    $this->get('/map')->assertRedirect('/login');
});

it('public registration cannot escalate role to admin', function () {
    $response = $this->post('/register', [
        'name' => 'Sneaky User',
        'email' => 'sneaky@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'role' => 'admin',
    ]);

    $response->assertRedirect('/dashboard');
    $this->assertDatabaseHas('users', [
        'email' => 'sneaky@example.com',
        'role' => 'field_officer',
    ]);
    expect(User::where('email', 'sneaky@example.com')->first()->role)->toBe('field_officer');
});

it('registration validates required fields', function () {
    $response = $this->post('/register', [
        'name' => '',
        'email' => 'not-an-email',
        'password' => 'short',
        'password_confirmation' => 'mismatch',
    ]);

    $response->assertSessionHasErrors(['name', 'email', 'password']);
    $this->assertGuest();
});

it('registration rejects duplicate email', function () {
    $existing = makeFieldOfficer();

    $response = $this->post('/register', [
        'name' => 'Another User',
        'email' => $existing->email,
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertSessionHasErrors('email');
    $this->assertDatabaseCount('users', 1);
});

it('user can request password reset link', function () {
    $user = makeFieldOfficer();

    Notification::fake();

    $this->post('/forgot-password', ['email' => $user->email])
        ->assertSessionHasNoErrors()
        ->assertSessionHas('status');

    Notification::assertSentTo($user, ResetPassword::class);
});

it('unknown email on forgot-password returns validation error', function () {
    Notification::fake();

    $this->post('/forgot-password', ['email' => 'nobody@example.com'])
        ->assertSessionHasErrors('email');
});

it('user can reset password with valid token', function () {
    $user = makeFieldOfficer();

    Notification::fake();
    $this->post('/forgot-password', ['email' => $user->email]);

    $rawToken = null;
    Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use (&$rawToken) {
        $rawToken = $notification->token;

        return true;
    });

    $response = $this->post('/reset-password', [
        'token' => $rawToken,
        'email' => $user->email,
        'password' => 'new-secret-password',
        'password_confirmation' => 'new-secret-password',
    ]);

    $response->assertRedirect()->assertSessionHas('status');

    $user->refresh();
    expect(Hash::check('new-secret-password', $user->password))->toBeTrue();

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'new-secret-password',
    ])->assertRedirect('/dashboard');
});

it('reset password with invalid token fails', function () {
    $user = makeFieldOfficer();

    Notification::fake();
    $this->post('/forgot-password', ['email' => $user->email]);

    $response = $this->post('/reset-password', [
        'token' => 'invalid-token',
        'email' => $user->email,
        'password' => 'new-secret-password',
        'password_confirmation' => 'new-secret-password',
    ]);

    $response->assertSessionHasErrors('email');

    $user->refresh();
    expect(Hash::check('password', $user->password))->toBeTrue();
});

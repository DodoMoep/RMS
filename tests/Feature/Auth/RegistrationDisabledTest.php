<?php

use App\Models\User;

// Eine Selbstregistrierung ist nicht vorgesehen: Benutzer legt ein Administrator
// in der Benutzerverwaltung an (siehe docs/03-module.md). Es gibt weder Route noch View.

test('the registration screen does not exist', function () {
    $this->get('/register')->assertNotFound();
});

test('a visitor cannot register themselves', function () {
    $response = $this->post('/register', [
        'name' => 'Selbstregistrierung',
        'email' => 'selbst@example.test',
        'password' => 'geheim-1234',
        'password_confirmation' => 'geheim-1234',
    ]);

    $response->assertStatus(404);
    $this->assertGuest();
    expect(User::where('email', 'selbst@example.test')->exists())->toBeFalse();
});

test('the login page does not offer a registration link', function () {
    $this->get('/login')
        ->assertOk()
        ->assertDontSee('/register', false);
});

<?php

use App\Models\User;

test('a user created by an administrator is verified and can use the application', function () {
    $admin = userWithPermissions(['user.add']);

    $this->actingAs($admin)
        ->post(route('users.store'), [
            'name' => 'Neuer Testbenutzer',
            'email' => 'neu@example.test',
            'password' => 'geheim-1234',
        ])
        ->assertRedirect(route('users.index'));

    $created = User::where('email', 'neu@example.test')->firstOrFail();

    expect($created->hasVerifiedEmail())->toBeTrue();

    $this->actingAs($created)->get(route('dashboard'))->assertOk();
});

test('creating a user requires the add permission', function () {
    $this->actingAs(userWithPermissions(['user.list']))
        ->post(route('users.store'), [
            'name' => 'Unberechtigt',
            'email' => 'unberechtigt@example.test',
            'password' => 'geheim-1234',
        ])
        ->assertForbidden();

    expect(User::where('email', 'unberechtigt@example.test')->exists())->toBeFalse();
});

test('an administrator changing the email of a user keeps the user verified', function () {
    $admin = userWithPermissions(['user.edit']);
    $user = User::factory()->create();

    $this->actingAs($admin)
        ->put(route('users.update', $user), [
            'name' => $user->name,
            'email' => 'geaendert@example.test',
        ])
        ->assertRedirect(route('users.index'));

    $user->refresh();

    expect($user->email)->toBe('geaendert@example.test')
        ->and($user->hasVerifiedEmail())->toBeTrue();
});

test('a user changing their own email has to verify the new address', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch('/profile', ['name' => $user->name, 'email' => 'eigene-aenderung@example.test'])
        ->assertSessionHasNoErrors();

    expect($user->refresh()->hasVerifiedEmail())->toBeFalse();
});

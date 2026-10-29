<?php

use App\Models\User;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;

function makeSuperAdmin(array $attributes = []): User
{
    Role::findOrCreate('super-admin', 'web');
    $user = User::factory()->create($attributes);
    $user->assignRole('super-admin');

    return $user;
}

// ------------------------------------------------------------- Umschalten

test('an administrator can deactivate a user', function () {
    $admin = userWithPermissions(['user.edit']);
    $user = User::factory()->create(['remember_token' => 'altes-token']);

    $this->actingAs($admin)
        ->patch(route('users.toggle-status', $user))
        ->assertRedirect()
        ->assertSessionHas('status');

    $user->refresh();

    expect($user->is_active)->toBeFalse()
        ->and($user->remember_token)->toBeNull();
    expect(Activity::where('log_name', 'admin')->where('event', 'user.deactivated')->exists())->toBeTrue();
});

test('an administrator can activate a deactivated user again', function () {
    $admin = userWithPermissions(['user.edit']);
    $user = User::factory()->inactive()->create();

    $this->actingAs($admin)->patch(route('users.toggle-status', $user))->assertRedirect();

    expect($user->refresh()->is_active)->toBeTrue();
    expect(Activity::where('log_name', 'admin')->where('event', 'user.activated')->exists())->toBeTrue();
});

test('toggling the status requires the edit permission', function () {
    $user = User::factory()->create();

    $this->actingAs(userWithPermissions(['user.list']))
        ->patch(route('users.toggle-status', $user))
        ->assertForbidden();

    expect($user->refresh()->is_active)->toBeTrue();
});

test('an administrator cannot deactivate their own account', function () {
    $admin = userWithPermissions(['user.edit']);

    $this->actingAs($admin)->patch(route('users.toggle-status', $admin))->assertForbidden();

    expect($admin->refresh()->is_active)->toBeTrue();
});

test('the last active super admin cannot be deactivated', function () {
    $admin = userWithPermissions(['user.edit']);
    $superAdmin = makeSuperAdmin();

    $this->actingAs($admin)
        ->patch(route('users.toggle-status', $superAdmin))
        ->assertSessionHasErrors();

    expect($superAdmin->refresh()->is_active)->toBeTrue();
});

test('a super admin can be deactivated while another active one remains', function () {
    $admin = userWithPermissions(['user.edit']);
    $first = makeSuperAdmin();
    makeSuperAdmin();

    $this->actingAs($admin)->patch(route('users.toggle-status', $first))->assertRedirect();

    expect($first->refresh()->is_active)->toBeFalse();
});

test('a deactivated super admin does not count as an active one', function () {
    $admin = userWithPermissions(['user.edit']);
    $active = makeSuperAdmin();
    makeSuperAdmin(['is_active' => false]);

    $this->actingAs($admin)
        ->patch(route('users.toggle-status', $active))
        ->assertSessionHasErrors();

    expect($active->refresh()->is_active)->toBeTrue();
});

// ------------------------------------------------------------------- Anmeldung

test('a deactivated user cannot log in even with the correct password', function () {
    $user = User::factory()->inactive()->create();

    $this->post('/login', ['email' => $user->email, 'password' => 'password'])
        ->assertSessionHasErrors(['email' => __('auth.deactivated')]);

    $this->assertGuest();
});

test('a wrong password for a deactivated user gives the generic error', function () {
    $user = User::factory()->inactive()->create();

    $this->post('/login', ['email' => $user->email, 'password' => 'falsches-passwort'])
        ->assertSessionHasErrors(['email' => __('auth.failed')]);

    $this->assertGuest();
});

test('a reactivated user can log in again', function () {
    $user = User::factory()->inactive()->create();
    $user->forceFill(['is_active' => true])->save();

    $this->post('/login', ['email' => $user->email, 'password' => 'password']);

    $this->assertAuthenticatedAs($user);
});

test('an active user can still log in', function () {
    $user = User::factory()->create();

    $this->post('/login', ['email' => $user->email, 'password' => 'password']);

    $this->assertAuthenticatedAs($user);
});

test('a user who is deactivated while logged in is logged out on the next request', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('dashboard'))->assertOk();

    $user->forceFill(['is_active' => false])->save();

    $this->get(route('dashboard'))
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors(['email' => __('auth.deactivated')]);

    $this->assertGuest();
});

test('a deactivated user cannot reach any protected page', function () {
    $user = userWithPermissions(['contacts.view']);
    $user->forceFill(['is_active' => false])->save();

    $this->actingAs($user)->get(route('contacts.index'))->assertRedirect(route('login'));
    $this->assertGuest();
});

// ---------------------------------------------------------------- Nachvollziehbarkeit

test('the orders of a deactivated user keep the creator', function () {
    $admin = userWithPermissions(['user.edit', 'orders.view']);
    $creator = User::factory()->create(['name' => 'Ausgeschiedener Mitarbeiter']);
    $order = makeOrder(makeContact(), $creator);

    $this->actingAs($admin)->patch(route('users.toggle-status', $creator));

    expect($order->refresh()->created_by)->toBe($creator->id);
    $this->actingAs($admin)
        ->get(route('orders.show', $order))
        ->assertOk()
        ->assertSee('Ausgeschiedener Mitarbeiter');
});

// ----------------------------------------------------------------- Oberflaeche

test('the user list shows the status and the matching button', function () {
    $admin = userWithPermissions(['user.list', 'user.edit']);
    User::factory()->create(['name' => 'Aktiver Benutzer']);
    User::factory()->inactive()->create(['name' => 'Inaktiver Benutzer']);

    $this->actingAs($admin)
        ->get(route('users.index'))
        ->assertOk()
        ->assertSee(__('users.status_active'))
        ->assertSee(__('users.status_inactive'))
        ->assertSee(__('users.deactivate'))
        ->assertSee(__('users.activate'));
});

test('the user list hides the status button without the edit permission', function () {
    $viewer = userWithPermissions(['user.list']);
    User::factory()->create();

    $this->actingAs($viewer)
        ->get(route('users.index'))
        ->assertOk()
        ->assertDontSee(__('users.deactivate'));
});

test('the status change is logged with the user attributes', function () {
    $admin = userWithPermissions(['user.edit']);
    $user = User::factory()->create();

    $this->actingAs($admin)->patch(route('users.toggle-status', $user));

    $entry = Activity::where('subject_type', User::class)->where('subject_id', $user->id)->latest('id')->first();

    expect($entry)->not->toBeNull();
});

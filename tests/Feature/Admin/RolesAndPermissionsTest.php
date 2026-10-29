<?php

use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

// ---------------------------------------------------------------------- Rollen

test('a role can be created with permissions', function () {
    $admin = userWithPermissions(['role.add', 'role.edit']);
    Permission::findOrCreate('orders.view', 'web');

    $this->actingAs($admin)
        ->post(route('roles.store'), ['name' => 'lager', 'permissions' => ['orders.view']])
        ->assertRedirect(route('roles.index'))
        ->assertSessionHas('status', __('roles.messages.created'));

    expect(Role::findByName('lager')->hasPermissionTo('orders.view'))->toBeTrue();
});

test('role changes take effect immediately for users of the role', function () {
    $admin = userWithPermissions(['role.add', 'role.edit']);
    Permission::findOrCreate('orders.view', 'web');
    Permission::findOrCreate('orders.pack', 'web');

    $role = Role::findOrCreate('packerrolle', 'web');
    $role->syncPermissions(['orders.view', 'orders.pack']);

    $user = User::factory()->create();
    $user->assignRole('packerrolle');

    expect($user->can('orders.pack'))->toBeTrue();

    $this->actingAs($admin)
        ->put(route('roles.update', $role), ['name' => 'packerrolle', 'permissions' => ['orders.view']])
        ->assertRedirect(route('roles.index'))
        ->assertSessionHas('status', __('roles.messages.updated'));

    // Ohne Zuruecksetzen des Rechte-Caches waere das Recht noch gueltig
    expect(User::find($user->id)->can('orders.pack'))->toBeFalse()
        ->and(User::find($user->id)->can('orders.view'))->toBeTrue();
});

test('a role can be deleted', function () {
    $admin = userWithPermissions(['role.delete']);
    $role = Role::findOrCreate('wegwerf', 'web');

    $this->actingAs($admin)
        ->delete(route('roles.destroy', $role))
        ->assertRedirect(route('roles.index'))
        ->assertSessionHas('status', __('roles.messages.deleted'));

    expect(Role::where('name', 'wegwerf')->exists())->toBeFalse();
});

test('the super admin role cannot be deleted', function () {
    $admin = userWithPermissions(['role.delete']);
    $role = Role::findOrCreate('super-admin', 'web');

    $this->actingAs($admin)
        ->delete(route('roles.destroy', $role))
        ->assertSessionHas('error', __('roles.messages.protected'));

    expect(Role::where('name', 'super-admin')->exists())->toBeTrue();
});

test('roles require the matching permissions', function () {
    $user = userWithPermissions(['role.list']);
    $role = Role::findOrCreate('geschuetzt', 'web');

    $this->actingAs($user)->get(route('roles.index'))->assertOk();
    $this->actingAs($user)->delete(route('roles.destroy', $role))->assertForbidden();
    $this->actingAs($user)->post(route('roles.store'), ['name' => 'neu'])->assertForbidden();
});

// ---------------------------------------------------------------- Berechtigungen

test('a permission can be created, renamed and deleted', function () {
    $admin = userWithPermissions(['perm.add', 'perm.edit', 'perm.delete']);

    $this->actingAs($admin)
        ->post(route('permissions.store'), ['name' => 'berichte.sehen'])
        ->assertRedirect(route('permissions.index'))
        ->assertSessionHas('status', __('permissions.messages.created'));

    $permission = Permission::where('name', 'berichte.sehen')->firstOrFail();

    $this->actingAs($admin)
        ->put(route('permissions.update', $permission), ['name' => 'berichte.ansehen'])
        ->assertRedirect(route('permissions.index'))
        ->assertSessionHas('status', __('permissions.messages.updated'));

    expect($permission->refresh()->name)->toBe('berichte.ansehen');

    $this->actingAs($admin)
        ->delete(route('permissions.destroy', $permission))
        ->assertRedirect(route('permissions.index'))
        ->assertSessionHas('status', __('permissions.messages.deleted'));

    expect(Permission::where('name', 'berichte.ansehen')->exists())->toBeFalse();
});

test('a deleted permission is no longer granted because the cache is reset', function () {
    $admin = userWithPermissions(['perm.delete']);
    Permission::findOrCreate('sonderrecht', 'web');

    $user = User::factory()->create();
    $user->givePermissionTo('sonderrecht');
    expect($user->can('sonderrecht'))->toBeTrue();

    $this->actingAs($admin)->delete(route('permissions.destroy', Permission::findByName('sonderrecht')));

    expect(User::find($user->id)->can('sonderrecht'))->toBeFalse();
});

test('permission names must be unique', function () {
    $admin = userWithPermissions(['perm.add', 'perm.edit']);
    Permission::findOrCreate('doppelt', 'web');

    $this->actingAs($admin)
        ->post(route('permissions.store'), ['name' => 'doppelt'])
        ->assertSessionHasErrors('name');
});

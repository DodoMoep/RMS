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

// ------------------------------------------------------ Validierung und Seiten

test('a role needs a name and only known permissions', function (array $payload, string $errorField) {
    $admin = userWithPermissions(['role.add', 'role.edit']);
    Permission::findOrCreate('orders.view', 'web');

    $this->actingAs($admin)
        ->post(route('roles.store'), $payload)
        ->assertSessionHasErrors($errorField);

    expect(Role::where('name', 'lager')->exists())->toBeFalse();
})->with([
    'no name' => [['name' => ''], 'name'],
    'name too long' => [['name' => str_repeat('a', 256)], 'name'],
    'unknown permission' => [['name' => 'lager', 'permissions' => ['gibt.es.nicht']], 'permissions.0'],
    'permissions not a list' => [['name' => 'lager', 'permissions' => 'orders.view'], 'permissions'],
]);

test('a role name must be unique but a role may keep its own name', function () {
    $admin = userWithPermissions(['role.add', 'role.edit']);
    Role::findOrCreate('vorhanden', 'web');
    $role = Role::findOrCreate('eigene', 'web');

    $this->actingAs($admin)
        ->post(route('roles.store'), ['name' => 'vorhanden'])
        ->assertSessionHasErrors('name');

    $this->actingAs($admin)
        ->put(route('roles.update', $role), ['name' => 'vorhanden'])
        ->assertSessionHasErrors('name');

    $this->actingAs($admin)
        ->put(route('roles.update', $role), ['name' => 'eigene'])
        ->assertSessionHasNoErrors();

    expect($role->refresh()->name)->toBe('eigene');
});

test('a role can be renamed', function () {
    $admin = userWithPermissions(['role.edit']);
    $role = Role::findOrCreate('alt', 'web');

    $this->actingAs($admin)
        ->put(route('roles.update', $role), ['name' => 'neu'])
        ->assertRedirect(route('roles.index'));

    expect($role->refresh()->name)->toBe('neu');
});

test('the role pages render', function () {
    $admin = userWithPermissions(['role.list', 'role.add', 'role.edit']);
    Permission::findOrCreate('orders.view', 'web');
    $role = Role::findOrCreate('sichtbar', 'web');
    $role->givePermissionTo('orders.view');

    $this->actingAs($admin)->get(route('roles.index'))->assertOk()->assertSee('sichtbar');
    $this->actingAs($admin)->get(route('roles.create'))->assertOk()->assertSee('orders.view');
    $this->actingAs($admin)->get(route('roles.edit', $role))->assertOk()->assertSee('sichtbar')->assertSee('orders.view');
});

test('roles of another guard cannot be opened or changed', function () {
    $admin = userWithPermissions(['role.edit', 'role.delete']);
    $apiRole = Role::create(['name' => 'api-rolle', 'guard_name' => 'api']);

    $this->actingAs($admin)->get(route('roles.edit', $apiRole))->assertNotFound();
    $this->actingAs($admin)->put(route('roles.update', $apiRole), ['name' => 'x'])->assertNotFound();
    $this->actingAs($admin)->delete(route('roles.destroy', $apiRole))->assertNotFound();

    expect($apiRole->refresh()->name)->toBe('api-rolle');
});

test('every role action needs its own permission', function () {
    $role = Role::findOrCreate('geschuetzt', 'web');
    $viewer = userWithPermissions(['role.list']);

    $this->actingAs($viewer)->get(route('roles.create'))->assertForbidden();
    $this->actingAs($viewer)->get(route('roles.edit', $role))->assertForbidden();
    $this->actingAs($viewer)->put(route('roles.update', $role), ['name' => 'x'])->assertForbidden();

    $this->actingAs(userWithPermissions([]))->get(route('roles.index'))->assertForbidden();

    expect($role->refresh()->name)->toBe('geschuetzt');
});

test('a permission needs a name', function (string $name) {
    $admin = userWithPermissions(['perm.add', 'perm.edit']);

    $this->actingAs($admin)
        ->post(route('permissions.store'), ['name' => $name])
        ->assertSessionHasErrors('name');
})->with(['empty' => '', 'too long' => str_repeat('a', 256)]);

test('a permission may keep its own name when it is changed', function () {
    $admin = userWithPermissions(['perm.edit']);
    $permission = Permission::findOrCreate('eigenes.recht', 'web');

    $this->actingAs($admin)
        ->put(route('permissions.update', $permission), ['name' => 'eigenes.recht'])
        ->assertSessionHasNoErrors();

    $other = Permission::findOrCreate('anderes.recht', 'web');

    $this->actingAs($admin)
        ->put(route('permissions.update', $other), ['name' => 'eigenes.recht'])
        ->assertSessionHasErrors('name');

    expect($other->refresh()->name)->toBe('anderes.recht');
});

test('the permission pages render', function () {
    $admin = userWithPermissions(['perm.list', 'perm.add', 'perm.edit']);
    $permission = Permission::findOrCreate('sichtbares.recht', 'web');

    $this->actingAs($admin)->get(route('permissions.index'))->assertOk()->assertSee('sichtbares.recht');
    $this->actingAs($admin)->get(route('permissions.create'))->assertOk();
    $this->actingAs($admin)->get(route('permissions.edit', $permission))->assertOk()->assertSee('sichtbares.recht');
});

test('permissions of another guard cannot be opened or changed', function () {
    $admin = userWithPermissions(['perm.edit', 'perm.delete']);
    $apiPermission = Permission::create(['name' => 'api.recht', 'guard_name' => 'api']);

    $this->actingAs($admin)->get(route('permissions.edit', $apiPermission))->assertNotFound();
    $this->actingAs($admin)->put(route('permissions.update', $apiPermission), ['name' => 'x'])->assertNotFound();
    $this->actingAs($admin)->delete(route('permissions.destroy', $apiPermission))->assertNotFound();

    expect($apiPermission->refresh()->name)->toBe('api.recht');
});

// ------------------------------------- Anlegen und Aendern getrennt berechtigt (M15)

test('creating a role needs only the add permission', function () {
    $creator = userWithPermissions(['role.add']);

    $this->actingAs($creator)
        ->post(route('roles.store'), ['name' => 'nur-anlegen'])
        ->assertRedirect(route('roles.index'));

    expect(Role::where('name', 'nur-anlegen')->exists())->toBeTrue();
});

test('changing a role needs the edit permission and not the add permission', function () {
    $role = Role::findOrCreate('wird-geaendert', 'web');

    $this->actingAs(userWithPermissions(['role.add']))
        ->put(route('roles.update', $role), ['name' => 'geaendert'])
        ->assertForbidden();

    $this->actingAs(userWithPermissions(['role.edit']))
        ->put(route('roles.update', $role), ['name' => 'geaendert'])
        ->assertRedirect(route('roles.index'));

    expect($role->refresh()->name)->toBe('geaendert');
});

test('creating a role is not possible with the edit permission alone', function () {
    $this->actingAs(userWithPermissions(['role.edit']))
        ->post(route('roles.store'), ['name' => 'nur-bearbeiten'])
        ->assertForbidden();

    expect(Role::where('name', 'nur-bearbeiten')->exists())->toBeFalse();
});

test('creating a permission needs only the add permission', function () {
    $creator = userWithPermissions(['perm.add']);

    $this->actingAs($creator)
        ->post(route('permissions.store'), ['name' => 'nur.anlegen'])
        ->assertRedirect(route('permissions.index'));

    expect(Permission::where('name', 'nur.anlegen')->exists())->toBeTrue();
});

test('changing a permission needs the edit permission and not the add permission', function () {
    $permission = Permission::findOrCreate('wird.geaendert', 'web');

    $this->actingAs(userWithPermissions(['perm.add']))
        ->put(route('permissions.update', $permission), ['name' => 'geaendert.recht'])
        ->assertForbidden();

    $this->actingAs(userWithPermissions(['perm.edit']))
        ->put(route('permissions.update', $permission), ['name' => 'geaendert.recht'])
        ->assertRedirect(route('permissions.index'));

    expect($permission->refresh()->name)->toBe('geaendert.recht');
});

test('creating a permission is not possible with the edit permission alone', function () {
    $this->actingAs(userWithPermissions(['perm.edit']))
        ->post(route('permissions.store'), ['name' => 'nur.bearbeiten'])
        ->assertForbidden();

    expect(Permission::where('name', 'nur.bearbeiten')->exists())->toBeFalse();
});

test('every permission action needs its own permission', function () {
    $permission = Permission::findOrCreate('geschuetztes.recht', 'web');
    $viewer = userWithPermissions(['perm.list']);

    $this->actingAs($viewer)->get(route('permissions.index'))->assertOk();
    $this->actingAs($viewer)->get(route('permissions.create'))->assertForbidden();
    $this->actingAs($viewer)->get(route('permissions.edit', $permission))->assertForbidden();
    $this->actingAs($viewer)->delete(route('permissions.destroy', $permission))->assertForbidden();

    expect(Permission::where('name', 'geschuetztes.recht')->exists())->toBeTrue();
});

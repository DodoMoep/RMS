<?php

use App\Models\Contact;
use App\Models\User;
use Database\Seeders\ContactPermissionsSeeder;
use Database\Seeders\OrderPermissionsSeeder;
use Database\Seeders\RentalPermissionsSeeder;
use Database\Seeders\TestContactSeeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Reihenfolge wie auf einer frischen Installation: Migrationen sind durch
 * (RefreshDatabase), danach laufen die Seeder.
 */
function seedPermissions(): void
{
    test()->seed(OrderPermissionsSeeder::class);
    test()->seed(RentalPermissionsSeeder::class);
    test()->seed(ContactPermissionsSeeder::class);
}

test('order manager receives all contact permissions', function () {
    seedPermissions();

    expect(Role::findByName('order-manager')->permissions->pluck('name'))
        ->toContain('contacts.view', 'contacts.create', 'contacts.edit', 'contacts.delete', 'contacts.toggle-status');
});

test('rental manager can manage contacts but not toggle the status', function () {
    seedPermissions();

    $names = Role::findByName('rental-manager')->permissions->pluck('name');

    expect($names)->toContain('contacts.view', 'contacts.create', 'contacts.edit', 'contacts.delete')
        ->and($names)->not->toContain('contacts.toggle-status');
});

test('rental staff can view and edit contacts but not delete them', function () {
    seedPermissions();

    $names = Role::findByName('rental-staff')->permissions->pluck('name');

    expect($names)->toContain('contacts.view', 'contacts.create', 'contacts.edit')
        ->and($names)->not->toContain('contacts.delete');
});

test('seeders no longer create the obsolete tenant and customer permissions', function () {
    seedPermissions();

    expect(Permission::where('name', 'like', 'tenants.%')->count())->toBe(0)
        ->and(Permission::where('name', 'like', 'customers.%')->count())->toBe(0);
});

test('permission seeders can run repeatedly', function () {
    seedPermissions();
    seedPermissions();

    expect(Permission::where('name', 'contacts.view')->count())->toBe(1)
        ->and(Role::where('name', 'order-manager')->count())->toBe(1);
});

test('a seeded rental staff user can open the contact list but cannot delete', function () {
    seedPermissions();
    $user = User::factory()->create();
    $user->assignRole('rental-staff');
    $contact = makeContact();

    $this->actingAs($user)->get(route('contacts.index'))->assertOk();
    $this->actingAs($user)->delete(route('contacts.destroy', $contact))->assertForbidden();
});

test('the base seeder creates the admin permissions without the unused settings permission', function () {
    App\Models\User::factory()->create();

    $this->seed(Database\Seeders\RolesAndPermissionsSeeder::class);

    $names = Permission::pluck('name');

    expect($names)->toContain('user.list', 'role.edit', 'perm.delete')
        ->and($names)->not->toContain('app.settings')
        ->and(App\Models\User::find(1)->hasRole('super-admin'))->toBeTrue();
});

test('test contact seeder creates customers with customer numbers', function () {
    $this->seed(TestContactSeeder::class);

    expect(Contact::count())->toBe(3)
        ->and(Contact::whereNull('customer_number')->count())->toBe(0);
});

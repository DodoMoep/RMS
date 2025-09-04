<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // create permissions
        Permission::create(['name' => 'user.list']);
        Permission::create(['name' => 'user.add']);
        Permission::create(['name' => 'user.edit']);
        Permission::create(['name' => 'user.delete']);
        Permission::create(['name' => 'role.list']);
        Permission::create(['name' => 'role.add']);
        Permission::create(['name' => 'role.edit']);
        Permission::create(['name' => 'role.delete']);
        Permission::create(['name' => 'perm.list']);
        Permission::create(['name' => 'perm.add']);
        Permission::create(['name' => 'perm.edit']);
        Permission::create(['name' => 'perm.delete']);
		Permission::create(['name' => 'app.settings']);

        // update cache to know about the newly created permissions (required if using WithoutModelEvents in seeders)
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();


        // create roles and assign created permissions
        $role = Role::create(['name' => 'super-admin']);
        $role->givePermissionTo(Permission::all());

        $user = User::find(1);
        $user->assignRole('super-admin');
    }
}

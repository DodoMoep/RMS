<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        $guardName = 'web';

        $contactPermissions = [
            'contacts.view',
            'contacts.create',
            'contacts.edit',
            'contacts.delete',
            'contacts.toggle-status',
        ];

        // Create contact permissions
        foreach ($contactPermissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => $guardName]);
        }

        // Map old permissions → new permissions
        $mapping = [
            'tenants.view'   => 'contacts.view',
            'tenants.create' => 'contacts.create',
            'tenants.edit'   => 'contacts.edit',
            'tenants.delete' => 'contacts.delete',
            'customers.view'          => 'contacts.view',
            'customers.create'        => 'contacts.create',
            'customers.edit'          => 'contacts.edit',
            'customers.delete'        => 'contacts.delete',
            'customers.toggle-status' => 'contacts.toggle-status',
        ];

        // Copy role assignments from old → new permissions
        foreach ($mapping as $oldPerm => $newPerm) {
            $old = Permission::where('name', $oldPerm)->where('guard_name', $guardName)->first();
            $new = Permission::where('name', $newPerm)->where('guard_name', $guardName)->first();

            if ($old && $new) {
                $roleIds = DB::table('role_has_permissions')
                    ->where('permission_id', $old->id)
                    ->pluck('role_id');

                foreach ($roleIds as $roleId) {
                    DB::table('role_has_permissions')->insertOrIgnore([
                        'permission_id' => $new->id,
                        'role_id'       => $roleId,
                    ]);
                }
            }
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down(): void
    {
        Permission::whereIn('name', [
            'contacts.view', 'contacts.create', 'contacts.edit',
            'contacts.delete', 'contacts.toggle-status',
        ])->delete();

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }
};

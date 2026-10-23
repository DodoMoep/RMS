<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class ContactPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Create permissions
        $permissions = [
            'contacts.view',
            'contacts.create',
            'contacts.edit',
            'contacts.delete',
            'contacts.toggle-status',
        ];

        foreach ($permissions as $name) {
            Permission::firstOrCreate(
                ['name' => $name],
                ['guard_name' => 'web']
            );
        }

        // Assign permissions to order-manager role
        $orderManagerRole = Role::firstOrCreate(
            ['name' => 'order-manager'],
            ['guard_name' => 'web']
        );

        $orderManagerRole->givePermissionTo($permissions);

        $this->command->info('Contact permissions created and assigned successfully!');
    }
}

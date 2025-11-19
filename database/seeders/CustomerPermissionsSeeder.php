<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class CustomerPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Create permissions
        $permissions = [
            'customers.view',
            'customers.create',
            'customers.edit',
            'customers.delete',
            'customers.toggle-status',
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

        $this->command->info('Customer permissions created and assigned successfully!');
    }
}

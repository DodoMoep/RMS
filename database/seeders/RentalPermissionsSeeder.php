<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RentalPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            // Inventory Items
            'inventory.view',
            'inventory.create',
            'inventory.edit',
            'inventory.delete',

            // Tenants
            'tenants.view',
            'tenants.create',
            'tenants.edit',
            'tenants.delete',

            // Halls
            'halls.view',
            'halls.create',
            'halls.edit',
            'halls.delete',

            // Rentals
            'rentals.view',
            'rentals.create',
            'rentals.edit',
            'rentals.delete',
            'rentals.handover',
            'rentals.return',

            // Protocols
            'protocols.view',
            'protocols.create',
            'protocols.edit',
            'protocols.sign',
            'protocols.pdf',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create Rental Manager role
        $rentalManager = Role::firstOrCreate(['name' => 'rental-manager']);
        $rentalManager->syncPermissions([
            'inventory.view',
            'inventory.create',
            'inventory.edit',
            'inventory.delete',
            'tenants.view',
            'tenants.create',
            'tenants.edit',
            'tenants.delete',
            'halls.view',
            'halls.create',
            'halls.edit',
            'halls.delete',
            'rentals.view',
            'rentals.create',
            'rentals.edit',
            'rentals.delete',
            'rentals.handover',
            'rentals.return',
            'protocols.view',
            'protocols.create',
            'protocols.edit',
            'protocols.sign',
            'protocols.pdf',
        ]);

        // Create Rental Staff role (limited permissions)
        $rentalStaff = Role::firstOrCreate(['name' => 'rental-staff']);
        $rentalStaff->syncPermissions([
            'inventory.view',
            'tenants.view',
            'tenants.create',
            'tenants.edit',
            'halls.view',
            'rentals.view',
            'rentals.create',
            'rentals.edit',
            'rentals.handover',
            'rentals.return',
            'protocols.view',
            'protocols.create',
            'protocols.sign',
            'protocols.pdf',
        ]);

        // Create Inventory Manager role
        $inventoryManager = Role::firstOrCreate(['name' => 'inventory-manager']);
        $inventoryManager->syncPermissions([
            'inventory.view',
            'inventory.create',
            'inventory.edit',
            'inventory.delete',
        ]);

        $this->command->info('Rental system permissions and roles created successfully!');
    }
}

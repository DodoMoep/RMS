<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class OrderPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            'orders.view',
            'orders.create',
            'orders.edit',
            'orders.delete',
            'orders.update-status',
            'orders.pack',
            'orders.print',
            'orders.view-history',
            'analytics.view',
            'analytics.export',
        ];

        foreach ($permissions as $permission) {
            \Spatie\Permission\Models\Permission::firstOrCreate(['name' => $permission]);
        }

        // Create Packer role if it doesn't exist
        $packerRole = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'packer']);
        $packerRole->syncPermissions(['orders.view', 'orders.pack', 'orders.update-status']);

        // Create Order Manager role
        $managerRole = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'order-manager']);
        $managerRole->syncPermissions([
            'orders.view',
            'orders.create',
            'orders.edit',
            'orders.delete',
            'orders.update-status',
            'orders.print',
            'orders.view-history',
            'analytics.view',
            'analytics.export',
        ]);

        $this->command->info('Order permissions and roles created successfully!');
    }
}

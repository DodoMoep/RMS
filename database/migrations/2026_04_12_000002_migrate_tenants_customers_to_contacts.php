<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            // Migrate tenants → contacts (type=tenant)
            $tenants = DB::table('tenants')->get();
            foreach ($tenants as $tenant) {
                DB::table('contacts')->insert([
                    'id'         => $tenant->id,
                    'type'       => 'tenant',
                    'name'       => $tenant->name,
                    'email'      => $tenant->email ?? null,
                    'phone'      => $tenant->phone ?? null,
                    'is_active'  => true,
                    'created_at' => $tenant->created_at ?? now(),
                    'updated_at' => $tenant->updated_at ?? now(),
                ]);
            }

            // Migrate customers → contacts (type=customer)
            // Phase 0 pre-flight UUID collision check must be done before running this!
            $customers = DB::table('customers')->get();
            foreach ($customers as $customer) {
                DB::table('contacts')->insert([
                    'id'                  => $customer->id,
                    'type'                => 'customer',
                    'name'                => $customer->name,
                    'contact_person_name' => $customer->contact_person_name ?? null,
                    'email'               => $customer->email ?? null,
                    'phone'               => $customer->phone ?? null,
                    'street'              => $customer->street ?? null,
                    'zip_code'            => $customer->zip_code ?? null,
                    'city'                => $customer->city ?? null,
                    'address_notes'       => $customer->address_notes ?? null,
                    'notes'               => $customer->notes ?? null,
                    'customer_number'     => $customer->customer_number ?? null,
                    'is_active'           => $customer->is_active ?? true,
                    'created_at'          => $customer->created_at ?? now(),
                    'updated_at'          => $customer->updated_at ?? now(),
                ]);
            }
        });
    }

    public function down(): void
    {
        DB::table('contacts')->delete();
    }
};

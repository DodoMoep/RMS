<?php

namespace Database\Seeders;

use App\Models\Customer;
use Illuminate\Database\Seeder;

class TestCustomerSeeder extends Seeder
{
    public function run(): void
    {
        Customer::create([
            'name' => 'Test Customer GmbH',
            'email' => 'test@customer.com',
            'phone' => '+49 123 456789',
            'address' => 'Teststraße 123, 12345 Teststadt',
            'notes' => 'Test customer for order management',
            'is_active' => true,
        ]);

        Customer::create([
            'name' => 'ABC Company',
            'email' => 'info@abc-company.de',
            'phone' => '+49 987 654321',
            'address' => 'Hauptstraße 1, 54321 Musterstadt',
            'notes' => 'Regular customer',
            'is_active' => true,
        ]);

        Customer::create([
            'name' => 'XYZ Corporation',
            'email' => 'contact@xyz-corp.com',
            'phone' => '+49 555 123456',
            'address' => 'Business Park 5, 67890 Firmencity',
            'is_active' => true,
        ]);
    }
}

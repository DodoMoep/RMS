<?php

namespace Database\Seeders;

use App\Enums\ContactType;
use App\Models\Contact;
use Illuminate\Database\Seeder;

class TestContactSeeder extends Seeder
{
    public function run(): void
    {
        Contact::create([
            'type' => ContactType::Customer,
            'name' => 'Test Customer GmbH',
            'email' => 'test@customer.example',
            'phone' => '+49 123 456789',
            'street' => 'Teststrasse 123',
            'zip_code' => '12345',
            'city' => 'Teststadt',
            'notes' => 'Test customer for order management',
            'is_active' => true,
        ]);

        Contact::create([
            'type' => ContactType::Customer,
            'name' => 'ABC Company',
            'email' => 'info@abc-company.example',
            'phone' => '+49 987 654321',
            'street' => 'Hauptstrasse 1',
            'zip_code' => '54321',
            'city' => 'Musterstadt',
            'notes' => 'Regular customer',
            'is_active' => true,
        ]);

        Contact::create([
            'type' => ContactType::Both,
            'name' => 'XYZ Corporation',
            'email' => 'contact@xyz-corp.example',
            'phone' => '+49 555 123456',
            'street' => 'Business Park 5',
            'zip_code' => '67890',
            'city' => 'Firmencity',
            'is_active' => true,
        ]);
    }
}

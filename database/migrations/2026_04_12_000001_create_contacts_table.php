<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contacts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type')->default('customer'); // tenant, customer, both
            $table->string('name');
            $table->string('contact_person_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('street')->nullable();
            $table->string('zip_code', 20)->nullable();
            $table->string('city', 100)->nullable();
            $table->text('address_notes')->nullable();
            $table->text('notes')->nullable();
            $table->string('customer_number', 20)->nullable()->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('type');
            $table->index('name');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};

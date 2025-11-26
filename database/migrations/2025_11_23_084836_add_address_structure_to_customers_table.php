<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('address');
            $table->string('street')->nullable()->after('contact_person_name');
            $table->string('zip_code', 10)->nullable()->after('street');
            $table->string('city', 100)->nullable()->after('zip_code');
            $table->text('address_notes')->nullable()->after('city');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['street', 'zip_code', 'city', 'address_notes']);
            $table->text('address')->nullable()->after('phone');
        });
    }
};

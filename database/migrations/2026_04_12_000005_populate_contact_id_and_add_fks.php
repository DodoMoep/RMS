<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Populate contact_id from tenant_id in rentals
        DB::statement('UPDATE rentals SET contact_id = tenant_id WHERE tenant_id IS NOT NULL');

        // Populate contact_id from customer_id in orders
        DB::statement('UPDATE orders SET contact_id = customer_id WHERE customer_id IS NOT NULL');

        // Add FK constraints and indexes
        Schema::table('rentals', function (Blueprint $table) {
            $table->index('contact_id');
            $table->foreign('contact_id')->references('id')->on('contacts')->nullOnDelete();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->index('contact_id');
            $table->foreign('contact_id')->references('id')->on('contacts')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('rentals', function (Blueprint $table) {
            $table->dropForeign(['contact_id']);
            $table->dropIndex(['contact_id']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['contact_id']);
            $table->dropIndex(['contact_id']);
        });

        DB::statement('UPDATE rentals SET contact_id = NULL');
        DB::statement('UPDATE orders SET contact_id = NULL');
    }
};

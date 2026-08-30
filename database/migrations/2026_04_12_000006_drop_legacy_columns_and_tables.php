<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop tenant_id from rentals
        Schema::table('rentals', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
            $table->dropColumn('tenant_id');
        });

        // Drop customer_id from orders
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
            $table->dropColumn('customer_id');
        });

        // Drop legacy tables
        Schema::dropIfExists('customers');
        Schema::dropIfExists('tenants');
    }

    public function down(): void
    {
        // Cannot easily reverse — data would be lost
        throw new \Exception('Migration F is irreversible. Restore from backup.');
    }
};

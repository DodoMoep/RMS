<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Das Loeschen eines Benutzers darf seine Auftraege nicht mehr mitloeschen (cascade).
        // Stattdessen bleibt der Auftrag erhalten, created_by wird leer.
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedBigInteger('created_by')->nullable()->change();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (DB::table('orders')->whereNull('created_by')->exists()) {
            throw new RuntimeException('Rueckgaengig nicht moeglich: Es gibt Auftraege ohne Ersteller (created_by ist leer).');
        }

        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedBigInteger('created_by')->nullable(false)->change();
            $table->foreign('created_by')->references('id')->on('users')->cascadeOnDelete();
        });
    }
};

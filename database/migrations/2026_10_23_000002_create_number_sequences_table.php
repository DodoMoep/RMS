<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Zaehler je Nummernkreis und Jahr, z. B. "order-2026" oder "customer-2026".
        // Die Zeile wird beim ersten Zugriff aus dem hoechsten vorhandenen Wert angelegt.
        Schema::create('number_sequences', function (Blueprint $table) {
            $table->string('name', 50)->primary();
            $table->unsignedBigInteger('last_value')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('number_sequences');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Gibt die Artikelnummern bereits geloeschter Artikel frei (siehe Article::booted).
     * Die Nummer steht weiter in den Auftragspositionen (article_sku).
     */
    public function up(): void
    {
        DB::table('articles')->whereNotNull('deleted_at')->update(['sku' => null]);
    }

    /**
     * Die frueheren Nummern lassen sich nicht rekonstruieren.
     */
    public function down(): void
    {
        //
    }
};

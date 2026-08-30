<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // order_items: index article_id for topItems() GROUP BY queries
        Schema::table('order_items', function (Blueprint $table) {
            $table->index('article_id');
        });

        // order_history: composite index for (order_id, event_type) filter pattern
        Schema::table('order_history', function (Blueprint $table) {
            $table->index(['order_id', 'event_type']);
        });

        // rentals: composite index for overlap check (hall_id + status)
        Schema::table('rentals', function (Blueprint $table) {
            $table->index(['hall_id', 'status']);
        });

        // protocol_items: indexes on FK columns for eager loading
        Schema::table('protocol_items', function (Blueprint $table) {
            $table->index('protocol_id');
            $table->index('inventory_item_id');
        });

        // photos: index on protocol_id for eager loading
        Schema::table('photos', function (Blueprint $table) {
            $table->index('protocol_id');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropIndex(['article_id']);
        });

        Schema::table('order_history', function (Blueprint $table) {
            $table->dropIndex(['order_id', 'event_type']);
        });

        Schema::table('rentals', function (Blueprint $table) {
            $table->dropIndex(['hall_id', 'status']);
        });

        Schema::table('protocol_items', function (Blueprint $table) {
            $table->dropIndex(['protocol_id']);
            $table->dropIndex(['inventory_item_id']);
        });

        Schema::table('photos', function (Blueprint $table) {
            $table->dropIndex(['protocol_id']);
        });
    }
};

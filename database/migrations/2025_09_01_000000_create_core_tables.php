<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('tenants', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->string('name');
            $t->string('email')->nullable();
            $t->string('phone')->nullable();
            $t->timestamps();
        });

        Schema::create('halls', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->string('name');
            $t->string('address')->nullable();
            $t->text('notes')->nullable();
            $t->timestamps();
        });

        Schema::create('inventory_items', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->string('name');
            $t->string('sku')->nullable();
            $t->text('description')->nullable();
            $t->timestamps();
        });

        Schema::create('hall_inventory', function (Blueprint $t) {
            $t->uuid('hall_id');
            $t->uuid('inventory_item_id');
            $t->integer('quantity')->default(1);
            $t->timestamps();
            $t->unique(['hall_id','inventory_item_id']);
            
            $t->foreign('hall_id')->references('id')->on('halls')->onDelete('cascade');
            $t->foreign('inventory_item_id')->references('id')->on('inventory_items')->onDelete('cascade');
        });

        Schema::create('rentals', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->uuid('tenant_id');
            $t->uuid('hall_id');
            $t->dateTime('start');
            $t->dateTime('end');
            $t->decimal('price',10,2)->nullable();
            $t->decimal('deposit',10,2)->nullable();
            $t->string('status')->default('scheduled'); // scheduled|active|closed|cancelled
            $t->timestamps();
            
            $t->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $t->foreign('hall_id')->references('id')->on('halls')->onDelete('cascade');
            $t->index('status');
            $t->index(['start', 'end']);
        });

        Schema::create('protocols', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->uuid('rental_id');
            $t->enum('type', ['handover','return']);
            $t->json('checklist')->nullable(); // z.B. Zählerstände
            $t->text('notes')->nullable();
            $t->json('meta')->nullable();      // ip, ua
            $t->string('pdf_path')->nullable();
            $t->string('pdf_sha256')->nullable();
            $t->timestamps();
            
            $t->foreign('rental_id')->references('id')->on('rentals')->onDelete('cascade');
            $t->unique(['rental_id', 'type']);
            $t->index('type');
        });

        Schema::create('protocol_items', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->uuid('protocol_id');
            $t->uuid('inventory_item_id')->nullable();
            $t->string('label');
            $t->enum('state', ['ok','missing','damaged'])->default('ok');
            $t->text('comment')->nullable();
            $t->decimal('charge',10,2)->nullable();
            $t->timestamps();
            
            $t->foreign('protocol_id')->references('id')->on('protocols')->onDelete('cascade');
            $t->foreign('inventory_item_id')->references('id')->on('inventory_items')->onDelete('set null');
        });

        Schema::create('signatures', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->uuid('protocol_id');
            $t->enum('role', ['tenant','landlord']);
            $t->string('signer_name');
            $t->string('png_path');       // public disk
            $t->timestamp('signed_at');
            $t->timestamps();
            
            $t->foreign('protocol_id')->references('id')->on('protocols')->onDelete('cascade');
            $t->index(['protocol_id', 'role']);
        });

        Schema::create('photos', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->uuid('protocol_id');
            $t->string('path'); // public disk
            $t->text('caption')->nullable();
            $t->timestamps();
            
            $t->foreign('protocol_id')->references('id')->on('protocols')->onDelete('cascade');
        });
    }

    public function down(): void {
        Schema::dropIfExists('photos');
        Schema::dropIfExists('signatures');
        Schema::dropIfExists('protocol_items');
        Schema::dropIfExists('protocols');
        Schema::dropIfExists('rentals');
        Schema::dropIfExists('hall_inventory');
        Schema::dropIfExists('inventory_items');
        Schema::dropIfExists('halls');
        Schema::dropIfExists('tenants');
    }
};

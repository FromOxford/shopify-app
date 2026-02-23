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
        Schema::create('shops', function (Blueprint $table) {
            $table->id();
            $table->string('domain')->unique();
            $table->string('access_token')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_products_synced_at')->nullable();
            $table->timestamp('last_orders_synced_at')->nullable();
            $table->timestamp('last_customers_synced_at')->nullable();
            $table->string('sync_status')->default('idle'); // idle | syncing | failed
            $table->text('sync_error')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shops');
    }
};

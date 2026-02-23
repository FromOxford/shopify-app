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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();

            $table->unsignedBigInteger('shopify_id')->index();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();

            $table->decimal('total_price', 14, 2)->nullable();
            $table->string('currency', 10)->nullable();
            $table->string('financial_status')->nullable();
            $table->string('fulfillment_status')->nullable();

            $table->timestamp('shopify_created_at')->nullable();
            $table->timestamps();

            $table->index(['shop_id', 'shopify_created_at']);
            $table->index(['shop_id', 'financial_status']);
            $table->unique(['shop_id', 'shopify_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};

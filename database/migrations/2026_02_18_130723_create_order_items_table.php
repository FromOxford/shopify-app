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
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('shopify_line_item_id')->nullable();
            $table->string('title');
            $table->integer('quantity');
            $table->decimal('price', 14, 2)->nullable();

            $table->index('order_id');
            $table->index('product_id');
            $table->timestamps();

            $table->unique(['shopify_line_item_id', 'order_id'], 'shopify_id_order_id_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};

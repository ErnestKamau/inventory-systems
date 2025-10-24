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
            $table->foreignId('order_id')->constrained(table: 'orders')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained(table: 'products')->cascadeOnDelete();
            $table->unsignedInteger('quantity')->default(0);
            $table->decimal('kilogram', 8, 3)->nullable();

            // CRITICAL: Snapshot price at order time
            // Protects against future product price changes
            $table->decimal('unit_price', 10, 2)->default(0.00);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('order_id');
            $table->index('product_id');
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

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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string(column: 'name')->unique();
            $table->foreignId(column: 'category_id')->constrained(table: 'categories')->cascadeOnDelete();
            $table->text(column: 'description')->nullable();
            $table->decimal(column: 'kilogram', total: 8, places: 3)->nullable();
            $table->decimal(column:'sale_price', total: 10, places:2)->default(value: 0.00);
            $table->decimal(column:'cost_price', total:10, places:2)->default(value:0.00);
            $table->unsignedInteger(column: 'in_stock')->default(value: 0);
            $table->unsignedInteger(column: 'minimum_stock')->default(value: 5);
            $table->boolean(column:'is_active')->default(value: true);
            $table->timestamps();

            $table->index(columns: 'is_active');
            $table->index(columns: 'category_id');
            $table->index(columns: ['is_active', 'in_stock']); // For low stock queries

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};

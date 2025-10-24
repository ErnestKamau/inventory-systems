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
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->unique()->constrained(table: 'orders')->cascadeOnDelete();
            $table->string('sale_number', 100)->unique();
            $table->string('customer_name',150);
            $table->string('customer_phone',15)->nullable();
            $table->decimal('total_amount',10, 2)->default(0.00);
            $table->decimal('cost_amount',10,2)->default(0.00);
            $table->decimal('profit_amount',10,2)->default(0.00);
            $table->enum('payment_status', ['fully-paid','partial','no-payment','overdue'])->default('no-payment');
            $table->timestamp('due_date')->nullable();
            $table->timestamps();

            $table->index('payment_status');
            $table->index('due_date');
            $table->index(['payment_status', 'due_date']); // Overdue queries
            $table->index('customer_phone'); // Customer lookup
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};

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
            
            // Nullable foreign key (guest orders allowed)
            $table->foreignId('user_id')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete(); // Don't delete orders if user deleted
            
            $table->string('customer_name', 150);
            $table->string('customer_phone', 15);
            $table->text('notes')->nullable();
            
            $table->enum('status', ['pending', 'confirmed', 'cancelled'])
                  ->default('pending');
            
            $table->enum('payment_status', ['PENDING', 'PAID', 'DEBT', 'FAILED'])
                  ->default('PENDING');
            
            // Separate date and time for precise tracking
            $table->date('order_date');
            $table->time('order_time');
            
            $table->timestamps();
            
            // Indexes
            $table->index('user_id');
            $table->index('status');
            $table->index('payment_status');
            $table->index(['order_date', 'status']); // Date range + status queries
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

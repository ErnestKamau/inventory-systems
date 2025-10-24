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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained('sales')->cascadeOnDelete();
            
            $table->enum('method', ['cash', 'mpesa', 'bank_transfer', 'card']);
            $table->decimal('amount', 10, 2)->default(0.00);
            
            // M-Pesa transaction code, bank reference, etc.
            $table->string('reference', 100)->nullable();
            $table->text('notes')->nullable();

            $table->timestamp('paid_at')->useCurrent(); // Auto-set to now
            $table->timestamps();
            

            $table->index('sale_id');
            $table->index('method');
            $table->index('paid_at'); // Date range reports

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};

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
        Schema::create('user', function (Blueprint $table): void {
            $table->id();
            $table->string(column: 'username', length: 150)->unique();
            $table->string(column: 'email')->unique();
            $table->string(column: 'password');
            $table->string(column: 'phone_number', length: 15)->unique();
            $table->enum(column: 'gender', allowed: ['Male', 'Female']);
            $table->enum(column: 'role', allowed: ['admin', 'customer'])->default(value: 'customer');
            $table->timestamps();

            $table->index(columns: 'role');
            $table->index(columns: ['role', 'created_at']);

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user');
    }
};

<?php
// database/migrations/0001_01_01_000000_create_users_table.php

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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            
            // Your custom fields
            $table->string('username', 150)->unique();
            $table->string('email')->unique();
            $table->string('password');
            $table->string('phone_number', 15)->unique();
            $table->enum('gender', ['Male', 'Female']);
            $table->enum('role', ['admin', 'customer'])->default('customer');
            
            $table->rememberToken(); // For "remember me" functionality
            $table->timestamps();
            
            // Indexes
            $table->index('role');
            $table->index(['role', 'created_at']);
        });

        // Password reset tokens table (keep this)
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        // Sessions table (keep this if using database sessions)
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};

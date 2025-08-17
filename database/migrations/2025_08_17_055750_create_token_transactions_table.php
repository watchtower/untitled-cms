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
        Schema::create('token_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('admin_id')->nullable()->constrained('users')->onDelete('set null'); // Admin who made the change
            $table->foreignId('token_id')->constrained()->onDelete('cascade');
            $table->integer('amount'); // Positive for additions, negative for deductions
            $table->integer('balance_before'); // Balance before this transaction
            $table->integer('balance_after'); // Balance after this transaction
            $table->string('reason'); // Reason for the transaction
            $table->string('type')->default('manual'); // manual, automatic, purchase, reward, etc.
            $table->json('metadata')->nullable(); // Additional context data
            $table->timestamp('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('token_transactions');
    }
};

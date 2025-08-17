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
        Schema::create('counter_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('action_type'); // ip_lookup, api_call, download, etc.
            $table->integer('amount'); // Positive for grants/resets, negative for usage
            $table->integer('balance_before'); // Balance before this transaction
            $table->integer('balance_after'); // Balance after this transaction
            $table->string('reason'); // Usage reason, reset, admin grant, etc.
            $table->string('type')->default('usage'); // usage, reset, grant, admin
            $table->json('metadata')->nullable(); // Additional context data
            $table->timestamp('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('counter_transactions');
    }
};

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
        Schema::create('resettable_counters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('action_type'); // ip_lookup, api_call, download, etc.
            $table->integer('balance')->default(0); // Current available balance
            $table->integer('max_balance'); // Maximum balance per reset period
            $table->enum('reset_period', ['daily', 'weekly', 'monthly'])->default('daily');
            $table->timestamp('last_reset')->nullable(); // When was this counter last reset
            $table->timestamps();
            
            // Unique constraint to prevent duplicate user-action pairs
            $table->unique(['user_id', 'action_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('resettable_counters');
    }
};

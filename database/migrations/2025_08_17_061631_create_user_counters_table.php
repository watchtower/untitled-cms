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
        Schema::create('user_counters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('counter_type_id')->constrained('counter_types')->onDelete('cascade');
            $table->integer('current_count')->default(0);
            $table->timestamp('last_reset_at')->nullable();
            $table->timestamps();

            // Unique constraint to prevent duplicate user-counter type pairs
            $table->unique(['user_id', 'counter_type_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_counters');
    }
};

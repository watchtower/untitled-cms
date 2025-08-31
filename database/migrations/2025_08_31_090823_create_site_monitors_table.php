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
        Schema::create('site_monitors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('url');
            $table->enum('status', ['active', 'inactive', 'failed'])->default('active');
            $table->integer('check_interval_minutes')->default(5);
            $table->json('response_data')->nullable();
            $table->integer('response_time_ms')->nullable();
            $table->integer('status_code')->nullable();
            $table->timestamp('last_checked_at')->nullable();
            $table->timestamp('last_success_at')->nullable();
            $table->timestamp('last_failure_at')->nullable();
            $table->text('failure_reason')->nullable();
            $table->integer('consecutive_failures')->default(0);
            $table->boolean('notifications_enabled')->default(true);
            $table->timestamps();
            
            $table->index(['user_id', 'status']);
            $table->index('last_checked_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('site_monitors');
    }
};

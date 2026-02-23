<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('ai_hubs', function (Blueprint $table) {
            $table->integer('monthly_quota')->default(1000);
            $table->integer('monthly_usage')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ai_hubs', function (Blueprint $table) {
            $table->dropColumn(['monthly_quota', 'monthly_usage']);
        });
    }
};

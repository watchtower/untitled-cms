<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use MongoDB\Laravel\Schema\Blueprint;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::connection('mongodb')->table('email_logs', function (Blueprint $table) {
            $table->unique('provider_message_id');
            $table->index('recipient');
            $table->index('subject');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('mongodb')->table('email_logs', function (Blueprint $table) {
            $table->dropIndex(['provider_message_id']);
            $table->dropIndex(['recipient']);
            $table->dropIndex(['subject']);
            $table->dropIndex(['status']);
        });
    }
};

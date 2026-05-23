<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use MongoDB\Laravel\Schema\Blueprint;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Rename the field in all documents
        DB::connection('mongodb')
            ->getCollection('email_logs')
            ->updateMany([], ['$rename' => ['resend_id' => 'provider_message_id']]);

        // 2. Drop the old index and create the new one
        Schema::connection('mongodb')->table('email_logs', function (Blueprint $table) {
            // MongoDB-Laravel provider handles index management
            try {
                $table->dropIndex(['resend_id']);
            } catch (Exception $e) {
                // Ignore if it doesn't exist (e.g. fresh DBs)
            }
            try {
                $table->unique('provider_message_id');
            } catch (Exception $e) {
                // Ignore if it already exists
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('mongodb')->table('email_logs', function (Blueprint $table) {
            try {
                $table->dropIndex(['provider_message_id']);
            } catch (Exception $e) {
                // Ignore if it doesn't exist
            }
            try {
                $table->unique('resend_id');
            } catch (Exception $e) {
                // Ignore if it already exists
            }
        });

        DB::connection('mongodb')
            ->getCollection('email_logs')
            ->updateMany([], ['$rename' => ['provider_message_id' => 'resend_id']]);
    }
};

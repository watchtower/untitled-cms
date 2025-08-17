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
        Schema::table('counter_transactions', function (Blueprint $table) {
            // Drop old columns and add new ones for proper counter type relationship
            $table->dropColumn(['action_type', 'amount', 'balance_before', 'balance_after']);

            // Add proper foreign key relationship and new column names
            $table->foreignId('counter_id')->after('user_id')->constrained('counter_types')->onDelete('cascade');
            $table->foreignId('admin_id')->after('counter_id')->nullable()->constrained('users')->onDelete('set null');
            $table->integer('count_change')->after('admin_id'); // Positive for grants/resets, negative for usage
            $table->integer('count_before')->after('count_change'); // Balance before this transaction
            $table->integer('count_after')->after('count_before'); // Balance after this transaction
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('counter_transactions', function (Blueprint $table) {
            // Drop new columns
            $table->dropForeign(['counter_id']);
            $table->dropForeign(['admin_id']);
            $table->dropColumn(['counter_id', 'admin_id', 'count_change', 'count_before', 'count_after']);

            // Add back old columns
            $table->string('action_type')->after('user_id');
            $table->integer('amount')->after('action_type');
            $table->integer('balance_before')->after('amount');
            $table->integer('balance_after')->after('balance_before');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'mongodb';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::connection($this->connection)->hasTable('vault_folders')) {
            Schema::connection($this->connection)->create('vault_folders', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->string('parent_id')->nullable()->index(); // Recursive parent
                $table->string('name');
                $table->string('path_slug')->index(); // Materialized path for querying e.g. /docs/finance
                $table->string('owner_id')->index(); // Reference to User ID
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('vault_folders');
    }
};

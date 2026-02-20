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
        Schema::connection($this->connection)->create('vault_files', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('folder_id')->nullable()->index(); // Null = root
            $table->string('storage_path'); // Internal path e.g. vault_secure/uuid.bin
            $table->string('original_name');
            $table->string('mime_type');
            $table->string('extension');
            $table->unsignedBigInteger('size_bytes');
            $table->string('hash_sha256')->nullable();
            $table->string('uploaded_by')->index(); // User ID
            $table->boolean('is_public')->default(false);
            $table->string('validation_status')->default('pending'); // pending, safe, infected

            // Image specific metadata
            $table->integer('width')->nullable();
            $table->integer('height')->nullable();
            $table->string('alt_text')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('vault_files');
    }
};

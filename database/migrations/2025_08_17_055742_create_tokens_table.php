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
        Schema::create('tokens', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // L33t Bytes, Special Tokens, etc.
            $table->string('slug')->unique(); // l33t-bytes, special-tokens
            $table->text('description')->nullable();
            $table->integer('default_count')->default(0); // Default amount given to new users
            $table->string('icon')->nullable(); // Icon class or emoji
            $table->string('color')->default('#6366f1'); // Hex color for UI
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tokens');
    }
};

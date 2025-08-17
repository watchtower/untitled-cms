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
        Schema::create('subscription_levels', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // L33t Padawan, L33t Jedi, L33t Master
            $table->string('slug')->unique(); // padawan, jedi, master
            $table->text('description')->nullable();
            $table->integer('level')->unique(); // 1, 2, 3
            $table->decimal('price', 8, 2)->default(0.00); // Monthly price
            $table->json('features')->nullable(); // Array of features
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_levels');
    }
};

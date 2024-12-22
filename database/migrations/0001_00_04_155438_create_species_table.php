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
        Schema::create('species', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100); // Name of the species
            $table->string('scientific_name', 100)->nullable(); // Scientific name of the species
            $table->string('common_name', 100)->nullable(); // Common name of the species
            $table->string('local_name', 100)->nullable(); // Local name of the species
            $table->enum('category', ['marine_mammals', 'marine_turtles', 'sharks_rays']); // Category of marine wildlife
            $table->text('description'); // Detailed description of the species
            $table->enum('conservation_status', ['CR', 'NT', 'EN', 'DD', 'VU', 'NA', 'LC']); // Conservation status
            $table->decimal('max_size', 5, 2)->nullable(); // Maximum size in cm
            $table->string('shape', 10)->nullable(); // Shape of the species
            $table->boolean('is_dangerous')->default(false); // Flag for dangerous species
            $table->boolean('is_active')->default(true); // Flag for active status
            $table->timestamps(); // Created at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('species');
    }
};

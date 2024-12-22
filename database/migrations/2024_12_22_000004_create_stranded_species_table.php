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
        Schema::create('stranded_species', function (Blueprint $table) {
            $table->id();
            $table->string('condition', 50); // Condition of the stranded species
            $table->decimal('latitude', 10, 7)->nullable(); // Latitude coordinate
            $table->decimal('longitude', 10, 7)->nullable(); // Longitude coordinate
            $table->enum('sex', ['Male', 'Female', 'Unknown']); // Sex of the stranded species
            $table->decimal('length', 5, 2)->nullable(); // Length in cm
            $table->decimal('weight', 5, 2)->nullable(); // Weight in kg
            $table->decimal('girth', 5, 2)->nullable(); // Girth in cm
            $table->text('disposition'); // Disposition of the stranded species
            $table->text('disposal_site')->nullable(); // Disposal site (nullable)
            $table->text('more_information')->nullable(); // Additional information (nullable)
            $table->timestamps(); // Created at and updated at timestamps
            $table->boolean('is_released')->default(false); // Flag for released status
            $table->boolean('is_active')->default(true); // Flag for active status
            $table->foreignId('species_id')->nullable()->constrained()->onDelete('set null'); // Foreign key for species
            $table->foreignId('stranded_incident_id')->constrained()->onDelete('cascade'); // Foreign key for stranded incident
            $table->foreignId('user_id')->constrained()->onDelete('set null'); // Foreign key for user
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stranded_species');
    }
};

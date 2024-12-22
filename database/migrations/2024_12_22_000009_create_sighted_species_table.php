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
        Schema::create('sighted_species', function (Blueprint $table) {
            $table->id();
            $table->enum('sex', ['Male', 'Female', 'Unknown']); // Sex of the species
            $table->string('size', 50)->nullable(); // Estimated size of the sighted species
            $table->string('condition', 50)->nullable(); // Condition of the species
            $table->decimal('latitude', 10, 7)->nullable(); // Latitude coordinate
            $table->decimal('longitude', 10, 7)->nullable(); // Longitude coordinate
            $table->text('species_description'); // Physical description of the species
            $table->text('behavior_observed'); // Behavior observed of the species
            $table->foreignId('species_id')->constrained()->onDelete('set null'); // Foreign key for species
            $table->foreignId('sighting_id')->constrained()->onDelete('cascade'); // Foreign key for sighting incident
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sighted_species');
    }
};

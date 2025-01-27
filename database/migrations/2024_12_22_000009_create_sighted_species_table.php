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
            $table->enum('size', ['tiny', 'small', 'medium', 'large', 'very_large', 'giant']);
            $table->text('species_description')->nullable(); // Physical description of the species
            $table->text('behavior_observed'); // Behavior observed of the species
            $table->foreignId('species_id')->nullable()->constrained()->onDelete('set null'); // Foreign key for species
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

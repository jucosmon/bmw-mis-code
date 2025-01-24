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
        Schema::create('sightings', function (Blueprint $table) {
            $table->id();
            $table->tinyInteger('certainty_level')->unsigned(); // Certainty level from 1 to 10
            $table->date('date'); // Date of the sighting incident
            $table->time('time')->nullable(); // Time of the sighting incident
            $table->tinyInteger('quantity')->unsigned(); // Number of species involved
            $table->decimal('latitude', 10, 7)->nullable(); // Latitude coordinate
            $table->decimal('longitude', 10, 7)->nullable(); // Longitude coordinate
            $table->text('detailed_location'); // Detailed description of the location
            $table->text('more_information')->nullable(); // Additional information about the sighting
            $table->enum('report_status', ['pending', 'verified', 'false'])->default('pending'); // Current status of the report
            $table->timestamps(); // Created at
            $table->boolean('is_active')->default( true); // Flag indicating if the sighting record is active
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Foreign key for user
            $table->foreignId('municipality_id')->constrained()->onDelete('cascade'); // Foreign key for municipality
            $table->foreignId('barangay_id')->constrained()->onDelete('cascade'); // Foreign key for barangay
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sightings');
    }
};

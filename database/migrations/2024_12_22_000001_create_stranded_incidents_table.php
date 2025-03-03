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
        Schema::create('stranded_incidents', function (Blueprint $table) {
            $table->id();
            $table->tinyInteger('certainty_level');
            $table->date('date');
            $table->time('time');
            $table->text('species_involved')->nullable();
            $table->unsignedTinyInteger('quantity');
            $table->enum('condition', ['alive', 'dead']);
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->enum('sea_state', ['calm', 'rough', 'moderate'])->nullable();
            $table->enum('weather', ['sunny', 'cloudy', 'rainy'])->nullable();
            $table->enum('beach_type', ['mangrove', 'rocky', 'sandy', 'reef'])->nullable();
            $table->text('detailed_location');
            $table->text('more_information')->nullable();
            $table->enum('report_status', ['pending', 'verified', 'completed', 'resolved', 'false'])->default('pending');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('municipality_id')->nullable();
            $table->unsignedInteger('barangay_id')->nullable();
            $table->unsignedInteger('user_id')->nullable();
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('municipality_id')->references('id')->on('municipalities')->onDelete('set null');
            $table->foreign('barangay_id')->references('id')->on('barangays')->onDelete('set null');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stranded_incidents');
    }
};

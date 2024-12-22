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
            $table->text('species_involved')->nullable(); // Nullable
            $table->unsignedTinyInteger('quantity');
            $table->string('condition', 30);
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('sea_state', 10);
            $table->string('weather', 10);
            $table->string('beach_type', 10);
            $table->text('detailed_location')->nullable(); // Nullable
            $table->text('more_information')->nullable(); // Nullable
            $table->enum('report_status', ['pending', 'verified', 'completed', 'resolved', 'false'])->default('pending');
            $table->boolean('is_false')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('municipality_id')->nullable(); // Nullable
            $table->unsignedInteger('barangay_id')->nullable(); // Nullable
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('municipality_id')->references('id')->on('municipalities')->onDelete('set null');
            $table->foreign('barangay_id')->references('id')->on('barangays')->onDelete('set null');
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

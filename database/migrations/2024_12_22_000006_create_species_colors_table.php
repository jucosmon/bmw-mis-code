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
        Schema::create('species_colors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('color_id')->constrained()->onDelete('cascade'); // Foreign key for color
            $table->foreignId('species_id')->constrained()->onDelete('cascade'); // Foreign key for species
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('species_colors');
    }
};

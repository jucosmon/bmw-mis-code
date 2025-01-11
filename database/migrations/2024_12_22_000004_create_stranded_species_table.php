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
            $table->tinyInteger('condition_code');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->enum('sex', ['male', 'female', 'unknown']);
            $table->decimal('length', 5, 2)->nullable();
            $table->decimal('weight', 5, 2)->nullable();
            $table->decimal('girth', 5, 2)->nullable();
            $table->text('disposition')->nullable();
            $table->text('disposal_site')->nullable();
            $table->text('more_information')->nullable();
            $table->timestamps();
            $table->boolean('is_released')->default(false);
            $table->boolean('is_active')->default(true);
            $table->foreignId('species_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('stranded_incident_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('set null');
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

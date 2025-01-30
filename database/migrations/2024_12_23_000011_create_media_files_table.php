<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('media_files', function (Blueprint $table) {
            $table->id();
            $table->string('path', 255);
            $table->string('name', 255);
            $table->string('caption', 255)->nullable();
            $table->enum('file_for', ['species', 'sighting', 'stranded_incident', 'comment','item']);
            $table->string('type', 60);
            $table->foreignId('species_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('comment_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('sighting_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('item_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('stranded_incident_id')->nullable()->constrained()->onDelete('cascade');
            $table->timestamps();
        });

        // Add the CHECK constraint after the table is created
        DB::statement('
            ALTER TABLE media_files
            ADD CONSTRAINT check_one_foreign_key
            CHECK (
                (species_id IS NOT NULL AND comment_id IS NULL AND sighting_id IS NULL AND item_id IS NULL AND stranded_incident_id IS NULL) OR
                (species_id IS NULL AND comment_id IS NOT NULL AND sighting_id IS NULL AND item_id IS NULL AND stranded_incident_id IS NULL) OR
                (species_id IS NULL AND comment_id IS NULL AND sighting_id IS NOT NULL AND item_id IS NULL AND stranded_incident_id IS NULL) OR
                (species_id IS NULL AND comment_id IS NULL AND sighting_id IS NULL AND item_id IS NOT NULL AND stranded_incident_id IS NULL) OR
                (species_id IS NULL AND comment_id IS NULL AND sighting_id IS NULL AND item_id IS NULL AND stranded_incident_id IS NOT NULL)
            )
    ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('media_files');
    }
};

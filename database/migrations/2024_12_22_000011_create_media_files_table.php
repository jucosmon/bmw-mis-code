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
        Schema::create('media_files', function (Blueprint $table) {
            $table->id();
            $table->binary('file'); // Binary data of the file
            $table->string('caption', 255); // Caption of the file
            $table->string('filename', 255); // Name of the file
            $table->string('file_for', 50); // Context in which the file is associated
            $table->string('file_type', 60); // MIME file type
            $table->foreignId('comment_id')->nullable()->constrained()->onDelete('cascade'); // Foreign key for comment
            $table->foreignId('sighting_id')->nullable()->constrained()->onDelete('cascade'); // Foreign key for sighting incident
            $table->foreignId('guideline_id')->nullable()->constrained()->onDelete('cascade'); // Foreign key for guideline
            $table->foreignId('stranded_incident_id')->nullable()->constrained()->onDelete('cascade'); // Foreign key for stranded incident
            $table->timestamps(); // Created at and updated at timestamps
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('media_files');
    }
};

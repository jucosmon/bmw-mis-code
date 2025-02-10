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
        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->text('text'); // The text content of the comment
            $table->timestamps(); // Created at and updated at timestamps
            $table->boolean('is_active')->default(true); // Flag for active status
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade'); // Foreign key for user
            $table->foreignId('stranded_incident_id')->constrained()->onDelete('cascade'); // Foreign key for stranded incident
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comments');
    }
};

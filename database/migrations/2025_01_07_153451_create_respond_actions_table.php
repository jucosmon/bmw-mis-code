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
        Schema::create('respond_actions', function (Blueprint $table) {
            $table->id();
            $table->enum('response_status', ['unavailable', 'ongoing', 'onsite']); // Response status of the participant
            $table->foreignId('stranded_incident_id')->constrained()->onDelete('cascade'); // Foreign key for stranded incident
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Foreign key for user
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('respond_actions');
    }
};

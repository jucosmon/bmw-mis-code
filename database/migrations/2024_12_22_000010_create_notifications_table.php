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
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->text('content'); // Message or content of the notification
            $table->enum('category', ['false', 'warning', 'general']); // Category of notification (e.g., "false", "warning", "new sighting", "general")
            $table->enum('notif_for', ['specific_user', 'responders', 'all']); // Indicator if it's for specific users, responders, or all
            $table->enum('type', ['sighting', 'stranding']); // Type of notification (e.g., "sighting", "stranding")
            $table->boolean('is_read')->default(false); // Flag indicating if the notification has been read
            $table->timestamps(); // Created at
            $table->foreignId('sighting_id')->nullable()->constrained()->onDelete('cascade'); // Foreign key for sighting
            $table->foreignId('stranded_incident_id')->nullable()->constrained()->onDelete('cascade'); // Foreign key for stranded incident
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade'); // Foreign key for user
            $table->foreignId('comment_id')->nullable()->constrained()->onDelete('cascade'); // Foreign key for user
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};

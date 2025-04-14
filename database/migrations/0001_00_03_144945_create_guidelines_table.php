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
        Schema::create('guidelines', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description');
            $table->enum('category',['marine_mammals', 'marine_turtles', 'sharks_rays']);
            $table->enum('user_role', ['public_user', 'barangay_official', 'lgu_responder', 'bpemo_staff', 'bpemo_admin']);
            $table->enum('language', ['english', 'bisaya', 'tagalog'])->default('english');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('guidelines');
    }
};

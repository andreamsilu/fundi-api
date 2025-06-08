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
        Schema::create('chats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('fundi_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('job_id')->nullable()->constrained()->onDelete('set null');
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();

            // Ensure unique chat between user and fundi for a specific job
            $table->unique(['user_id', 'fundi_id', 'job_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chats');
    }
}; 
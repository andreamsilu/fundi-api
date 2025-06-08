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
        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chat_id')->constrained()->onDelete('cascade');
            $table->foreignId('sender_id')->constrained('users')->onDelete('cascade');
            $table->text('message');
            $table->string('type')->default('text'); // text, image, file, etc.
            $table->json('metadata')->nullable(); // For additional data like file URLs, image dimensions, etc.
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['chat_id', 'created_at']);
            $table->index(['sender_id', 'read_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_messages');
    }
}; 
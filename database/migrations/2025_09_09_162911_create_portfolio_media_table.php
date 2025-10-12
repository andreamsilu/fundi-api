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
        Schema::create('portfolio_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('portfolio_id')->constrained('portfolio')->onDelete('cascade');
            $table->string('file_path', 255);
            $table->enum('file_type', ['image', 'video']); // Changed from media_type to file_type
            $table->unsignedInteger('file_size')->default(0); // File size in bytes
            $table->string('caption', 500)->nullable(); // Caption/description
            $table->unsignedSmallInteger('order')->default(0); // Changed from order_index to order
            $table->boolean('is_featured')->default(false); // Mark featured image
            $table->timestamps();
            
            $table->index(['portfolio_id', 'order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('portfolio_media');
    }
};

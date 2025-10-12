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
        Schema::create('job_media', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('job_id'); // Will be linked after job_postings table is created
            $table->enum('media_type', ['image', 'video', 'document']);
            $table->string('file_path', 255);
            $table->string('file_name', 255);
            $table->unsignedInteger('file_size')->default(0); // File size in bytes
            $table->string('mime_type', 100)->nullable();
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('order')->default(0);
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
            
            $table->index(['job_id', 'order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_media');
    }
};

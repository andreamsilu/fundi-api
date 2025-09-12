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
        Schema::create('fundi_application_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('section_name'); // personal_info, contact_info, professional_info, documents, portfolio
            $table->json('section_data'); // The actual data for this section
            $table->boolean('is_completed')->default(false);
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
            
            // Ensure one section per user per section name
            $table->unique(['user_id', 'section_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fundi_application_sections');
    }
};
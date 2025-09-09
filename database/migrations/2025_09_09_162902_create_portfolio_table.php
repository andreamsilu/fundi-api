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
        Schema::create('portfolio', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fundi_id')->constrained('users')->onDelete('cascade');
            $table->string('title', 150)->nullable();
            $table->text('description')->nullable();
            $table->text('skills_used')->nullable();
            $table->integer('duration_hours')->unsigned()->nullable();
            $table->decimal('budget', 10, 2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('portfolio');
    }
};

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
        Schema::table('portfolio', function (Blueprint $table) {
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending')->after('budget');
            $table->text('rejection_reason')->nullable()->after('status');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null')->after('rejection_reason');
            $table->timestamp('approved_at')->nullable()->after('approved_by');
            $table->boolean('is_visible')->default(false)->after('approved_at');
            
            // Add index for efficient querying
            $table->index(['fundi_id', 'status']);
            $table->index(['status', 'is_visible']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('portfolio', function (Blueprint $table) {
            $table->dropIndex(['fundi_id', 'status']);
            $table->dropIndex(['status', 'is_visible']);
            $table->dropColumn([
                'status',
                'rejection_reason', 
                'approved_by',
                'approved_at',
                'is_visible'
            ]);
        });
    }
};
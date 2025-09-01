<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        if (Schema::hasTable('chat_messages')) {
            Schema::drop('chat_messages');
        }

        if (Schema::hasTable('chats')) {
            Schema::drop('chats');
        }

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        // Intentionally left empty. Legacy tables are deprecated and should not be recreated.
    }
};

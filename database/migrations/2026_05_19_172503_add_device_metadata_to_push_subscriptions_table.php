<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::connection(config('webpush.database_connection'))
            ->table(config('webpush.table_name'), function (Blueprint $table): void {
                $table->string('user_agent', 512)->nullable()->after('content_encoding');
                $table->timestamp('last_used_at')->nullable()->after('user_agent');
            });
    }

    public function down(): void
    {
        Schema::connection(config('webpush.database_connection'))
            ->table(config('webpush.table_name'), function (Blueprint $table): void {
                $table->dropColumn(['user_agent', 'last_used_at']);
            });
    }
};

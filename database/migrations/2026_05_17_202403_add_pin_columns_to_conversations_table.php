<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table): void {
            $table->timestamp('pinned_at')->nullable()->after('last_comment_at');
            $table->foreignId('pinned_by_user_id')->nullable()->after('pinned_at')->constrained('users')->nullOnDelete();

            $table->index(['group_id', 'pinned_at']);
        });
    }

    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table): void {
            $table->dropIndex(['group_id', 'pinned_at']);
            $table->dropConstrainedForeignId('pinned_by_user_id');
            $table->dropColumn('pinned_at');
        });
    }
};

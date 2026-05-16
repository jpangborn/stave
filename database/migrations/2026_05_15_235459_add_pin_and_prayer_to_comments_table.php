<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('comments', function (Blueprint $table): void {
            $table->timestamp('pinned_at')->nullable()->after('approved_at');
            $table->foreignId('pinned_by_user_id')->nullable()->after('pinned_at')->constrained('users')->nullOnDelete();
            $table->boolean('is_prayer')->default(false)->after('pinned_by_user_id');

            $table->index(['commentable_type', 'commentable_id', 'pinned_at'], 'comments_pinned_idx');
        });
    }

    public function down(): void
    {
        Schema::table('comments', function (Blueprint $table): void {
            $table->dropIndex('comments_pinned_idx');
            $table->dropConstrainedForeignId('pinned_by_user_id');
            $table->dropColumn(['pinned_at', 'is_prayer']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        $indexName = 'muted_commentables_user_commentable_unique';
        $legacyIndexName = 'muted_commentables_user_id_commentable_type_commentable_id_unique';

        if (! Schema::hasTable('muted_commentables')) {
            Schema::create('muted_commentables', function (Blueprint $table) use ($indexName) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->morphs('commentable');
                $table->timestamps();

                $table->unique(
                    ['user_id', 'commentable_type', 'commentable_id'],
                    $indexName,
                );
            });

            return;
        }

        // Repair partial state from a prior aborted deploy: table exists but the
        // unique index was never added (MySQL DDL isn't transactional, so a
        // mid-migration crash can leave the table without its constraints).
        if (
            ! Schema::hasIndex('muted_commentables', $indexName)
            && ! Schema::hasIndex('muted_commentables', $legacyIndexName)
        ) {
            Schema::table('muted_commentables', function (Blueprint $table) use ($indexName) {
                $table->unique(
                    ['user_id', 'commentable_type', 'commentable_id'],
                    $indexName,
                );
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('muted_commentables');
    }
};

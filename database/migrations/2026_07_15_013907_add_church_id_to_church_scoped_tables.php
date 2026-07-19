<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    /**
     * Tables whose models are the root of top-level queries and therefore
     * carry their own church_id; strictly parent-accessed children (sheets,
     * recordings, conversations, ...) inherit their church via the parent.
     *
     * @var array<int, string>
     */
    private array $tables = [
        'people',
        'households',
        'services',
        'templates',
        'songs',
        'readings',
        'series',
        'groups',
        'prayer_requests',
        'pastoral_notes',
        'liturgy_elements',
        'prayer_schedule_settings',
    ];

    public function up(): void
    {
        foreach ($this->tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->foreignId('church_id')->nullable()->index()->constrained()->cascadeOnDelete();
            });
        }

        Schema::table('services', function (Blueprint $table): void {
            $table->index(['church_id', 'date']);
        });

        Schema::table('liturgy_elements', function (Blueprint $table): void {
            $table->index(['church_id', 'assignee_id']);
        });

        Schema::table('prayer_schedule_settings', function (Blueprint $table): void {
            $table->unique('church_id');
        });
    }

    public function down(): void
    {
        Schema::table('prayer_schedule_settings', function (Blueprint $table): void {
            $table->dropUnique(['church_id']);
        });

        Schema::table('services', function (Blueprint $table): void {
            $table->dropIndex(['church_id', 'date']);
        });

        Schema::table('liturgy_elements', function (Blueprint $table): void {
            $table->dropIndex(['church_id', 'assignee_id']);
        });

        foreach ($this->tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->dropConstrainedForeignId('church_id');
            });
        }
    }
};

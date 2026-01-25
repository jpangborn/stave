<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('readings', function (Blueprint $table) {
            $table->foreignId('series_id')
                ->nullable()
                ->after('text')
                ->constrained('series')
                ->nullOnDelete();
            $table->unsignedSmallInteger('series_order')
                ->nullable()
                ->after('series_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('readings', function (Blueprint $table) {
            $table->dropForeign(['series_id']);
            $table->dropColumn(['series_id', 'series_order']);
        });
    }
};

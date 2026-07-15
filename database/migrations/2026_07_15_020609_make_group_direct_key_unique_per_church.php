<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('groups', function (Blueprint $table): void {
            $table->unique(['church_id', 'direct_key']);
        });

        Schema::table('groups', function (Blueprint $table): void {
            $table->dropUnique(['direct_key']);
        });
    }

    public function down(): void
    {
        Schema::table('groups', function (Blueprint $table): void {
            $table->unique('direct_key');
        });

        Schema::table('groups', function (Blueprint $table): void {
            $table->dropUnique(['church_id', 'direct_key']);
        });
    }
};

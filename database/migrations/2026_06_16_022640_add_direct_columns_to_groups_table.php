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
        Schema::table('groups', function (Blueprint $table): void {
            $table->boolean('is_direct')->default(false)->index();
            $table->string('direct_key')->nullable()->unique();
            $table->string('name')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('groups', function (Blueprint $table): void {
            $table->dropUnique(['direct_key']);
            $table->dropColumn(['is_direct', 'direct_key']);
            $table->string('name')->nullable(false)->change();
        });
    }
};

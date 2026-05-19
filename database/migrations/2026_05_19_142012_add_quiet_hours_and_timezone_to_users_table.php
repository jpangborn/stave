<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->time('quiet_hours_start')->nullable()->after('is_active');
            $table->time('quiet_hours_end')->nullable()->after('quiet_hours_start');
            $table->string('timezone', 64)->nullable()->after('quiet_hours_end');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['quiet_hours_start', 'quiet_hours_end', 'timezone']);
        });
    }
};

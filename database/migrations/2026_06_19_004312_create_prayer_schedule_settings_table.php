<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('prayer_schedule_settings', function (Blueprint $table): void {
            $table->id();
            $table->unsignedTinyInteger('cycle_weeks')->default(8);
            $table->string('group_by')->default('alpha');
            $table->json('include_statuses');
            $table->date('anchor_date');
            $table->timestamps();
        });

        // Settings rows are created lazily per church by
        // PrayerScheduleSettings::current(); nothing to seed here.
    }

    public function down(): void
    {
        Schema::dropIfExists('prayer_schedule_settings');
    }
};

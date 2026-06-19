<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
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

        // Seed the single settings row so the rotation always has defaults to read.
        DB::table('prayer_schedule_settings')->insert([
            'cycle_weeks' => 8,
            'group_by' => 'alpha',
            'include_statuses' => json_encode(['member', 'catechumen']),
            'anchor_date' => Carbon::now()->startOfWeek(Carbon::MONDAY)->toDateString(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('prayer_schedule_settings');
    }
};

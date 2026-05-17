<?php

use App\Support\SectionTone;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('liturgy_elements', function (Blueprint $table) {
            $table->string('section_color')->nullable()->after('description');
        });

        DB::table('liturgy_elements')
            ->where('type', 'section')
            ->whereNull('section_color')
            ->orderBy('id')
            ->each(function (object $row) {
                DB::table('liturgy_elements')
                    ->where('id', $row->id)
                    ->update(['section_color' => SectionTone::pick((string) $row->name)]);
            });
    }

    public function down(): void
    {
        Schema::table('liturgy_elements', function (Blueprint $table) {
            $table->dropColumn('section_color');
        });
    }
};

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
        Schema::table('people', function (Blueprint $table): void {
            $table->boolean('baptized')->default(false)->after('birth_date');
            $table->date('baptism_date')->nullable()->after('baptized');

            $table->foreignId('household_id')
                ->nullable()
                ->after('baptism_date')
                ->constrained('households')
                ->nullOnDelete();

            $table->string('household_role')->nullable()->after('household_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('people', function (Blueprint $table): void {
            $table->dropForeign(['household_id']);
            $table->dropColumn([
                'baptized',
                'baptism_date',
                'household_id',
                'household_role',
            ]);
        });
    }
};

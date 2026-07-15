<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    /**
     * A user who belongs to several churches has a distinct Person record in
     * each; the pivot tracks which Person represents the user per church.
     * users.person_id remains as the single-church fallback.
     */
    public function up(): void
    {
        Schema::table('church_user', function (Blueprint $table): void {
            $table->foreignId('person_id')->nullable()->constrained()->nullOnDelete();
        });

        DB::table('users')
            ->whereNotNull('person_id')
            ->orderBy('id')
            ->each(function (object $user): void {
                DB::table('church_user')
                    ->where('user_id', $user->id)
                    ->whereNull('person_id')
                    ->update(['person_id' => $user->person_id]);
            });
    }

    public function down(): void
    {
        Schema::table('church_user', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('person_id');
        });
    }
};

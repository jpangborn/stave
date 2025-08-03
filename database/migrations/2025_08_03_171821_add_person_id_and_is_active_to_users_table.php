<?php

use App\Models\Person;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Step 1: Add nullable person_id
        Schema::table('users', function (Blueprint $table) {
            $table->integer('person_id')->unsigned()->default(0)->after('id');
        });

        // Step 2: Backfill people and update users
        DB::table('users')
            ->get()
            ->each(function ($user) {
                $personId = DB::table('people')->insertGetId([
                    'first_name' => explode(' ', $user->name)[0],
                    'last_name' => explode(' ', $user->name)[1],
                    'email' => $user->email,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::table('users')
                    ->where('id', $user->id)
                    ->update(['person_id' => $personId]);
            });

        // Step 3: Make person_id required and add FK
        Schema::table('users', function (Blueprint $table) {
            $table
                ->foreign('person_id')
                ->references('id')
                ->on('people')
                ->cascadeOnDelete();
        });

        // Step 4: Add is_active
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('password');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeignIdFor(Person::class);
            $table->dropColumn(['person_id', 'is_active']);
        });
    }
};

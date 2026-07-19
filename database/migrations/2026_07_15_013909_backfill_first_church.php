<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class() extends Migration
{
    /**
     * Assign all pre-tenancy data to a first church. Idempotent: only rows
     * still missing a church_id are touched, so re-running is safe.
     *
     * @var array<int, string>
     */
    private array $tables = [
        'people',
        'households',
        'services',
        'templates',
        'songs',
        'readings',
        'series',
        'groups',
        'prayer_requests',
        'pastoral_notes',
        'liturgy_elements',
        'prayer_schedule_settings',
        'user_access_roles',
    ];

    public function up(): void
    {
        if (DB::table('users')->doesntExist()) {
            return;
        }

        $name = config('app.first_church_name', 'First Church');

        $churchId = DB::table('churches')->insertGetId([
            'name' => $name,
            'slug' => Str::slug($name),
            'timezone' => config('app.first_church_timezone', 'America/New_York'),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        foreach ($this->tables as $table) {
            DB::table($table)->whereNull('church_id')->update(['church_id' => $churchId]);
        }

        $now = Carbon::now();

        DB::table('users')->orderBy('id')->chunkById(1000, function ($users) use ($churchId, $now): void {
            DB::table('church_user')->insertOrIgnore(
                $users->map(fn (object $user): array => [
                    'church_id' => $churchId,
                    'user_id' => $user->id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->toArray(),
            );
        });

        DB::table('users')->whereNull('current_church_id')->update(['current_church_id' => $churchId]);
    }

    public function down(): void
    {
        // Data backfill; intentionally irreversible.
    }
};

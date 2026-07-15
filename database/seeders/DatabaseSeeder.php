<?php

namespace Database\Seeders;

use App\Models\Church;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Tables assigned to the seeded church after the raw-insert seeders run
     * (they bypass Eloquent, so church_id cannot be filled automatically).
     *
     * @var array<int, string>
     */
    private array $churchScopedTables = [
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

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $church = Church::query()->firstOrCreate(
            ['slug' => 'first-church'],
            ['name' => 'First Church', 'timezone' => 'America/New_York'],
        );

        $this->call([
            PeopleTableSeeder::class,
            UsersTableSeeder::class,
            SongsTableSeeder::class,
            ReadingsTableSeeder::class,
            TemplatesTableSeeder::class,
            ServicesTableSeeder::class,
            LiturgyElementsTableSeeder::class,
            GroupSeeder::class,
            PrayerRequestSeeder::class,
        ]);

        foreach ($this->churchScopedTables as $table) {
            DB::table($table)->whereNull('church_id')->update(['church_id' => $church->id]);
        }

        DB::table('users')->orderBy('id')->pluck('id')->each(function (int $userId) use ($church): void {
            DB::table('church_user')->insertOrIgnore([
                'church_id' => $church->id,
                'user_id' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        DB::table('users')->whereNull('current_church_id')->update(['current_church_id' => $church->id]);
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
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
    }
}

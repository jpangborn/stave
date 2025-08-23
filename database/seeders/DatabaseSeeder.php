<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $this->call(PeopleTableSeeder::class);
        $this->call(UsersTableSeeder::class);
        $this->call(SongsTableSeeder::class);
        $this->call(ReadingsTableSeeder::class);
        $this->call(TemplatesTableSeeder::class);
        $this->call(ServicesTableSeeder::class);
        $this->call(LiturgyElementsTableSeeder::class);
    }
}

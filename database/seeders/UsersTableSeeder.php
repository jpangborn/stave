<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class UsersTableSeeder extends Seeder
{
    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {

        \DB::table('users')->delete();

        \DB::table('users')->insert([
            0 => [
                'id' => 1,
                'name' => 'Joshua Pangborn',
                'email' => 'joshua@pangborn.cloud',
                'email_verified_at' => null,
                'password' => '$2y$12$KwOZGgzyFGyxv430gAelquAOTNNnY5ClEqgSUHe4.rpYjMroKqo8K',
                'remember_token' => '0VhAN5CiPx0HzzeFNoxyMg949TeODLvSYSYTOFj26IdfLAVSsZMh7RvA5N8Q',
                'created_at' => '2025-05-31 01:37:31',
                'updated_at' => '2025-05-31 01:37:31',
                'person_id' => 1,
                'is_active' => 1,
            ],
        ]);

    }
}

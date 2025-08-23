<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class PeopleTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('people')->delete();
        
        \DB::table('people')->insert(array (
            0 => 
            array (
                'id' => 1,
                'first_name' => 'Joshua',
                'last_name' => 'Pangborn',
                'email' => 'joshua@pangborn.cloud',
                'birth_date' => '1981-04-24',
                'gender' => 'male',
                'created_at' => '2025-08-03 18:39:25',
                'updated_at' => '2025-08-03 18:39:25',
            ),
        ));
        
        
    }
}
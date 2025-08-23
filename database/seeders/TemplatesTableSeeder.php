<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class TemplatesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('templates')->delete();
        
        \DB::table('templates')->insert(array (
            0 => 
            array (
                'id' => 1,
                'name' => 'Sunday Morning Service',
                'default' => 1,
                'created_at' => '2025-06-07 23:09:27',
                'updated_at' => '2025-06-07 23:09:27',
            ),
        ));
        
        
    }
}
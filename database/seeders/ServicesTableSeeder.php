<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ServicesTableSeeder extends Seeder
{
    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {

        \DB::table('services')->delete();

        \DB::table('services')->insert([
            0 => [
                'id' => 3,
                'title' => 'Sunday Morning Service – Jul 6, 2025',
                'date' => '2025-07-06 00:00:00',
                'template_id' => 1,
                'notes' => null,
                'created_at' => '2025-07-05 15:49:21',
                'updated_at' => '2025-07-05 15:49:21',
            ],
            1 => [
                'id' => 4,
                'title' => 'Sunday Morning Service – Jul 13, 2025',
                'date' => '2025-07-13 00:00:00',
                'template_id' => 1,
                'notes' => null,
                'created_at' => '2025-07-06 13:20:07',
                'updated_at' => '2025-07-06 13:20:07',
            ],
            2 => [
                'id' => 5,
                'title' => 'Sunday Morning Service – Jul 20, 2025',
                'date' => '2025-07-20 00:00:00',
                'template_id' => 1,
                'notes' => null,
                'created_at' => '2025-07-13 23:03:52',
                'updated_at' => '2025-07-13 23:03:52',
            ],
            3 => [
                'id' => 6,
                'title' => 'Sunday Morning Service – Jul 27, 2025',
                'date' => '2025-07-27 00:00:00',
                'template_id' => 1,
                'notes' => null,
                'created_at' => '2025-07-20 13:13:52',
                'updated_at' => '2025-07-20 13:13:52',
            ],
            4 => [
                'id' => 7,
                'title' => 'Sunday Morning Service – Aug 3, 2025',
                'date' => '2025-08-03 00:00:00',
                'template_id' => 1,
                'notes' => null,
                'created_at' => '2025-08-02 13:46:54',
                'updated_at' => '2025-08-02 13:46:54',
            ],
            5 => [
                'id' => 8,
                'title' => 'Sunday Morning Service – Aug 10, 2025',
                'date' => '2025-08-10 00:00:00',
                'template_id' => 1,
                'notes' => null,
                'created_at' => '2025-08-09 17:44:30',
                'updated_at' => '2025-08-09 17:44:30',
            ],
            6 => [
                'id' => 9,
                'title' => 'Sunday Morning Service – Aug 17, 2025',
                'date' => '2025-08-17 00:00:00',
                'template_id' => 1,
                'notes' => null,
                'created_at' => '2025-08-10 12:55:23',
                'updated_at' => '2025-08-10 12:55:23',
            ],
            7 => [
                'id' => 10,
                'title' => 'Sunday Morning Service – Aug 24, 2025',
                'date' => '2025-08-24 00:00:00',
                'template_id' => 1,
                'notes' => null,
                'created_at' => '2025-08-20 01:06:14',
                'updated_at' => '2025-08-20 01:06:14',
            ],
        ]);

    }
}

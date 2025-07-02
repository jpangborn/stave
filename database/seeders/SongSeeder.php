<?php

namespace Database\Seeders;

use App\Models\Song;
use Illuminate\Database\Seeder;

class SongSeeder extends Seeder
{
    private $dataFile = 'songs.csv';

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        Song::truncate();

        $songs = fopen(base_path('database/data/'.$this->dataFile), 'r');

        while ($song = fgetcsv($songs)) {
            Song::create([
                'name' => $song[0],
                'ccli_number' => $song[1],
                'lyrics' => $song[2],
            ]);
        }
    }
}

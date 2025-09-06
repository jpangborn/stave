<?php

namespace App\Livewire\Forms;

use App\Models\Song;
use Livewire\Attributes\Validate;
use Livewire\Form;

class SongForm extends Form
{
    public ?Song $song = null;

    #[Validate('required|string')]
    public string $name;

    public ?string $authors = null;

    public ?string $ccli_number = null;

    public ?string $copyright = null;

    public ?string $lyrics = null;

    public function setSong(Song $song): void
    {
        $this->song = $song;

        $this->name = $song->name;
        $this->authors = $song->authors;
        $this->ccli_number = $song->ccli_number;
        $this->copyright = $song->copyright;
        $this->lyrics = $song->lyrics;
    }

    public function store(): void
    {
        $this->validate();

        Song::create(
            $this->only(['name', 'authors', 'ccli_number', 'copyright', 'lyrics'])
        );
    }

    public function update(): void
    {
        $this->validate();

        $this->song->update(
            $this->only(['name', 'authors', 'ccli_number', 'copyright', 'lyrics'])
        );
    }
}

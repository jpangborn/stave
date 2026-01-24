<?php

use App\Models\Song;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('digital-ocean');
    $this->user = User::factory()->create();
    $this->song = Song::factory()->create();
    $this->actingAs($this->user);
});

test('guests cannot access the upload component', function (): void {
    auth()->logout();

    Livewire::test('songs.upload-files', ['song' => $this->song])
        ->assertForbidden();
});

test('authenticated users can upload audio files with valid mime types', function (string $extension, string $mimeType): void {
    $file = UploadedFile::fake()->create("test.{$extension}", 1024, $mimeType);

    Livewire::test('songs.upload-files', ['song' => $this->song])
        ->set('file', $file)
        ->set('description', 'Test recording')
        ->call('save')
        ->assertHasNoErrors();

    expect($this->song->recordings()->count())->toBe(1);
})->with([
    'mp3 with audio/mpeg' => ['mp3', 'audio/mpeg'],
    'm4a with audio/mp4 (iOS Safari)' => ['m4a', 'audio/mp4'],
    'm4a with audio/x-m4a (iOS Safari variant)' => ['m4a', 'audio/x-m4a'],
    'm4a with audio/m4a (desktop Safari)' => ['m4a', 'audio/m4a'],
    'aac with audio/aac' => ['aac', 'audio/aac'],
    'aac with audio/x-aac' => ['aac', 'audio/x-aac'],
]);

test('authenticated users can upload a pdf file', function (): void {
    $file = UploadedFile::fake()->create('test.pdf', 1024, 'application/pdf');

    Livewire::test('songs.upload-files', ['song' => $this->song])
        ->set('file', $file)
        ->set('description', 'Test sheet music')
        ->call('save')
        ->assertHasNoErrors();

    expect($this->song->sheets()->count())->toBe(1);
});

test('file upload rejects invalid mime types', function (): void {
    $file = UploadedFile::fake()->create('test.exe', 1024, 'application/x-msdownload');

    Livewire::test('songs.upload-files', ['song' => $this->song])
        ->set('file', $file)
        ->set('description', 'Test invalid file')
        ->call('save')
        ->assertHasErrors(['file']);

    expect($this->song->recordings()->count())->toBe(0);
    expect($this->song->sheets()->count())->toBe(0);
});

test('file upload requires a description', function (): void {
    $file = UploadedFile::fake()->create('test.mp3', 1024, 'audio/mpeg');

    Livewire::test('songs.upload-files', ['song' => $this->song])
        ->set('file', $file)
        ->set('description', '')
        ->call('save')
        ->assertHasErrors(['description' => 'required']);
});

test('file upload enforces max file size', function (): void {
    // 10420 KB is the max, so 11000 KB should fail
    $file = UploadedFile::fake()->create('test.mp3', 11000, 'audio/mpeg');

    Livewire::test('songs.upload-files', ['song' => $this->song])
        ->set('file', $file)
        ->set('description', 'Too large')
        ->call('save')
        ->assertHasErrors(['file']);
});

<?php

use App\Models\Song;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->song = Song::factory()->create();
    $this->actingAs($this->user);
});

test('guests cannot access the upload component', function (): void {
    auth()->logout();

    Livewire::test('songs.upload-files', ['song' => $this->song])
        ->assertForbidden();
});

test('authenticated users can upload an mp3 file with audio/mpeg mime type', function (): void {
    $file = UploadedFile::fake()->create('test.mp3', 1024, 'audio/mpeg');

    Livewire::test('songs.upload-files', ['song' => $this->song])
        ->set('file', $file)
        ->set('description', 'Test recording')
        ->call('save')
        ->assertHasNoErrors();

    expect($this->song->recordings()->count())->toBe(1);
});

test('authenticated users can upload an m4a file with audio/mp4 mime type (iOS Safari)', function (): void {
    $file = UploadedFile::fake()->create('test.m4a', 1024, 'audio/mp4');

    Livewire::test('songs.upload-files', ['song' => $this->song])
        ->set('file', $file)
        ->set('description', 'Test recording')
        ->call('save')
        ->assertHasNoErrors();

    expect($this->song->recordings()->count())->toBe(1);
});

test('authenticated users can upload an m4a file with audio/x-m4a mime type (iOS Safari variant)', function (): void {
    $file = UploadedFile::fake()->create('test.m4a', 1024, 'audio/x-m4a');

    Livewire::test('songs.upload-files', ['song' => $this->song])
        ->set('file', $file)
        ->set('description', 'Test recording')
        ->call('save')
        ->assertHasNoErrors();

    expect($this->song->recordings()->count())->toBe(1);
});

test('authenticated users can upload an m4a file with audio/m4a mime type (desktop Safari)', function (): void {
    $file = UploadedFile::fake()->create('test.m4a', 1024, 'audio/m4a');

    Livewire::test('songs.upload-files', ['song' => $this->song])
        ->set('file', $file)
        ->set('description', 'Test recording')
        ->call('save')
        ->assertHasNoErrors();

    expect($this->song->recordings()->count())->toBe(1);
});

test('authenticated users can upload an aac file with audio/aac mime type', function (): void {
    $file = UploadedFile::fake()->create('test.aac', 1024, 'audio/aac');

    Livewire::test('songs.upload-files', ['song' => $this->song])
        ->set('file', $file)
        ->set('description', 'Test recording')
        ->call('save')
        ->assertHasNoErrors();

    expect($this->song->recordings()->count())->toBe(1);
});

test('authenticated users can upload an aac file with audio/x-aac mime type', function (): void {
    $file = UploadedFile::fake()->create('test.aac', 1024, 'audio/x-aac');

    Livewire::test('songs.upload-files', ['song' => $this->song])
        ->set('file', $file)
        ->set('description', 'Test recording')
        ->call('save')
        ->assertHasNoErrors();

    expect($this->song->recordings()->count())->toBe(1);
});

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

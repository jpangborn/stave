<?php

use App\Models\Song;
use App\Models\User;
use Illuminate\Http\Testing\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

/**
 * Create a file with proper magic bytes for MIME type detection.
 *
 * Livewire's TemporaryUploadedFile uses finfo to detect MIME types from file
 * content, so fake files without proper headers fail validation. This helper
 * creates files with the correct magic bytes for each audio/document format.
 */
function createFileWithMagicBytes(string $extension, string $mimeType, int $sizeInKb = 1): File
{
    $magicBytes = match ($mimeType) {
        'audio/mpeg' => "\xFF\xFB\x90\x00",
        'audio/mp4', 'audio/x-m4a', 'audio/m4a' => "\x00\x00\x00\x1C\x66\x74\x79\x70\x4D\x34\x41\x20",
        'audio/aac', 'audio/x-aac' => "\xFF\xF1\x50\x80",
        'application/pdf' => "%PDF-1.4\n",
        default => '',
    };

    $content = $magicBytes.str_repeat("\x00", max(0, ($sizeInKb * 1024) - strlen($magicBytes)));

    return File::createWithContent("test.{$extension}", $content)->mimeType($mimeType);
}

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
    $file = createFileWithMagicBytes($extension, $mimeType);

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
    $file = createFileWithMagicBytes('pdf', 'application/pdf');

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
    $file = createFileWithMagicBytes('mp3', 'audio/mpeg');

    Livewire::test('songs.upload-files', ['song' => $this->song])
        ->set('file', $file)
        ->set('description', '')
        ->call('save')
        ->assertHasErrors(['description' => 'required']);
});

test('file upload enforces max file size', function (): void {
    // 10420 KB is the max, so 11000 KB should fail
    $file = createFileWithMagicBytes('mp3', 'audio/mpeg', 11000);

    Livewire::test('songs.upload-files', ['song' => $this->song])
        ->set('file', $file)
        ->set('description', 'Too large')
        ->call('save')
        ->assertHasErrors(['file']);
});

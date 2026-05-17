<?php

namespace App\Observers;

use App\Models\ConversationFile;
use Illuminate\Support\Facades\Storage;

class ConversationFileObserver
{
    public function deleted(ConversationFile $file): void
    {
        if (Storage::disk($file->disk)->exists($file->path)) {
            Storage::disk($file->disk)->delete($file->path);
        }
    }
}

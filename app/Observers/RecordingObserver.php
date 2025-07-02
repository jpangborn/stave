<?php

namespace App\Observers;

use App\Models\Recording;
use Illuminate\Support\Facades\Storage;

class RecordingObserver
{
    /**
     * Handle the Recording "created" event.
     */
    public function created(Recording $recording): void
    {
        //
    }

    /**
     * Handle the Recording "updated" event.
     */
    public function updated(Recording $recording): void
    {
        //
    }

    /**
     * Handle the Recording "deleted" event.
     */
    public function deleted(Recording $recording): void
    {
        if (Storage::disk('recordings')->exists($recording->filename)) {
            Storage::disk('recordings')->delete($recording->filename);
        }
    }

    /**
     * Handle the Recording "restored" event.
     */
    public function restored(Recording $recording): void
    {
        //
    }

    /**
     * Handle the Recording "force deleted" event.
     */
    public function forceDeleted(Recording $recording): void
    {
        //
    }
}

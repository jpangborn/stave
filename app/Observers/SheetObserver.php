<?php

namespace App\Observers;

use App\Models\Sheet;
use Illuminate\Support\Facades\Storage;

class SheetObserver
{
    /**
     * Handle the Sheet "created" event.
     */
    public function created(Sheet $sheet): void
    {
        //
    }

    /**
     * Handle the Sheet "updated" event.
     */
    public function updated(Sheet $sheet): void
    {
        //
    }

    /**
     * Handle the Sheet "deleted" event.
     */
    public function deleted(Sheet $sheet): void
    {
        if (Storage::disk('sheets')->exists($sheet->filename)) {
            Storage::disk('sheets')->delete($sheet->filename);
        }
    }

    /**
     * Handle the Sheet "restored" event.
     */
    public function restored(Sheet $sheet): void
    {
        //
    }

    /**
     * Handle the Sheet "force deleted" event.
     */
    public function forceDeleted(Sheet $sheet): void
    {
        //
    }
}

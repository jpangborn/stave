<?php

namespace App\Models;

use App\Observers\RecordingObserver;
use Database\Factories\RecordingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ObservedBy([RecordingObserver::class])]
#[Fillable(['description', 'filename'])]
class Recording extends Model
{
    /** @use HasFactory<RecordingFactory> */
    use HasFactory;

    /** @return BelongsTo<Song, $this> */
    public function song(): BelongsTo
    {
        return $this->belongsTo(Song::class);
    }
}

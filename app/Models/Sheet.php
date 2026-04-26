<?php

namespace App\Models;

use App\Observers\SheetObserver;
use Database\Factories\SheetFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ObservedBy([SheetObserver::class])]
#[Fillable(['description', 'filename'])]
class Sheet extends Model
{
    /** @use HasFactory<SheetFactory> */
    use HasFactory;

    /** @return BelongsTo<Song, $this> */
    public function song(): BelongsTo
    {
        return $this->belongsTo(Song::class);
    }
}

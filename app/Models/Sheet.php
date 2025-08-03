<?php

namespace App\Models;

use App\Observers\SheetObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ObservedBy([SheetObserver::class])]
class Sheet extends Model
{
    /** @use HasFactory<\Database\Factories\SheetFactory> */
    use HasFactory;

    protected $fillable = ['description', 'filename'];

    /**
     * @return BelongsTo<Song,Sheet>
     */
    public function song(): BelongsTo
    {
        return $this->belongsTo(Song::class);
    }
}

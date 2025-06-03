<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Song extends Model
{
    /** @use HasFactory<\Database\Factories\SongFactory> */
    use HasFactory;

    protected $fillable = ["name", "ccli_number", "copyright", "lyrics"];

    /**
     * @return HasMany<Recording,Song>
     */
    public function recordings(): HasMany
    {
        return $this->hasMany(Recording::class);
    }

    /**
     * @return HasMany<Sheet,Song>
     */
    public function sheets(): HasMany
    {
        return $this->hasMany(Sheet::class);
    }
}

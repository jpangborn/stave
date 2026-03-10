<?php

namespace App\Models;

use App\Enums\GroupMessaging;
use App\Enums\GroupVisibility;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    /** @use HasFactory<\Database\Factories\GroupFactory> */
    use HasFactory;

    protected $fillable = ['name', 'description', 'image', 'visibility', 'messaging'];

    /** @return array<string, class-string> */
    protected function casts(): array
    {
        return [
            'visibility' => GroupVisibility::class,
            'messaging' => GroupMessaging::class,
        ];
    }
}

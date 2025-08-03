<?php

namespace App\Models;

use App\Enums\Gender;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Person extends Model
{
    /** @use HasFactory<\Database\Factories\PersonFactory> */
    use HasFactory;

    protected $fillable = [
        "first_name",
        "last_name",
        "email",
        "birth_date",
        "gender",
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            "gender" => Gender::class,
            "birth_date" => "date",
        ];
    }

    /**
     * @return HasOne<User,Person>
     */
    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }

    public function fullName(): Attribute
    {
        return Attribute::make(
            get: fn($value) => "{$this->first_name} {$this->last_name}",
        );
    }
}

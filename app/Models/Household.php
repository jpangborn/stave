<?php

namespace App\Models;

use App\Models\Concerns\BelongsToChurch;
use Database\Factories\HouseholdFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'address'])]
class Household extends Model
{
    /** @use HasFactory<HouseholdFactory> */
    use BelongsToChurch, HasFactory;

    protected static function booted(): void
    {
        static::deleting(function (Household $household): void {
            $household->people()->update([
                'household_id' => null,
                'household_role' => null,
            ]);
        });
    }

    /** @return HasMany<Person, $this> */
    public function people(): HasMany
    {
        return $this->hasMany(Person::class);
    }
}

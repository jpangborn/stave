<?php

namespace App\Models;

use App\Enums\Office;
use Database\Factories\PersonOfficeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'person_id',
    'kind',
    'started_on',
    'ended_on',
    'end_reason',
])]
class PersonOffice extends Model
{
    /** @use HasFactory<PersonOfficeFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'kind' => Office::class,
            'started_on' => 'date',
            'ended_on' => 'date',
        ];
    }

    /** @return BelongsTo<Person, $this> */
    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }
}

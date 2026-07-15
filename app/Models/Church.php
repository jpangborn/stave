<?php

namespace App\Models;

use App\Enums\AccessRole;
use App\Support\CurrentChurch;
use Database\Factories\ChurchFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

#[Fillable(['name', 'slug', 'timezone', 'email', 'phone', 'address', 'website', 'logo_path'])]
class Church extends Model
{
    /** @use HasFactory<ChurchFactory> */
    use HasFactory;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'join_token_rotated_at' => 'datetime',
        ];
    }

    /** @return BelongsToMany<User, $this> */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    /** @return HasMany<Service, $this> */
    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    /** @return HasMany<Person, $this> */
    public function people(): HasMany
    {
        return $this->hasMany(Person::class);
    }

    /** @return HasMany<ChurchInvitation, $this> */
    public function invitations(): HasMany
    {
        return $this->hasMany(ChurchInvitation::class);
    }

    public function hasMember(User $user): bool
    {
        return $this->users()->whereKey($user->id)->exists();
    }

    /**
     * Add a user to this church: creates (or finds) the church's Person
     * record for them, links it on the membership pivot, grants the given
     * roles, and makes this church current when the user has none.
     *
     * @param  array<int, AccessRole>  $roles
     */
    public function addMember(User $user, array $roles = []): void
    {
        $person = app(CurrentChurch::class)->runAs($this, function () use ($user): Person {
            [$first, $last] = array_pad(preg_split('/\s+/', trim($user->name), 2), 2, '');

            return Person::firstOrCreate([
                'email' => Str::lower($user->email),
            ], [
                'first_name' => $first,
                'last_name' => $last,
            ]);
        });

        $this->users()->syncWithoutDetaching([$user->id => ['person_id' => $person->id]]);

        if ($user->current_church_id === null) {
            $user->forceFill(['current_church_id' => $this->id])->save();
        }

        foreach ($roles as $role) {
            $user->grantAccessRole($role, $this);
        }
    }

    /**
     * Rotate the shareable join token, invalidating any previously
     * distributed join links and QR codes.
     */
    public function regenerateJoinToken(): void
    {
        $this->forceFill([
            'join_token' => Str::random(64),
            'join_token_rotated_at' => now(),
        ])->save();
    }

    public function disableJoinToken(): void
    {
        $this->forceFill([
            'join_token' => null,
            'join_token_rotated_at' => now(),
        ])->save();
    }

    /**
     * Generate a unique slug for a new church.
     */
    public static function uniqueSlugFor(string $name): string
    {
        $base = Str::slug($name) !== '' ? Str::slug($name) : 'church';
        $slug = $base;
        $suffix = 2;

        while (self::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }

    protected function logoUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->logo_path
            ? Storage::disk('digital-ocean')->url($this->logo_path)
            : null);
    }
}

<?php

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Casts\Attribute;

trait HasGravatar
{
    public function gravatar(): Attribute
    {
        return Attribute::make(
            get: function () {
                $email = $this->email ?? "";
                $hash = hash("sha256", strtolower(trim($email)));

                return "https://www.gravatar.com/avatar/{$hash}";
            },
        );
    }
}

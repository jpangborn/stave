<?php

namespace App\Models\Scopes;

use App\Support\CurrentChurch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Constrains queries to the current church. Deliberately a no-op when no
 * current church resolves (migrations, seeders, queued model restoration).
 */
class ChurchScope implements Scope
{
    /**
     * @param  Builder<covariant Model>  $builder
     */
    public function apply(Builder $builder, Model $model): void
    {
        $churchId = app(CurrentChurch::class)->id();

        if ($churchId !== null) {
            $builder->where($model->qualifyColumn('church_id'), $churchId);
        }
    }
}

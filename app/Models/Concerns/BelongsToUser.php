<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

trait BelongsToUser
{
    public static function bootBelongsToUser(): void
    {
        // Defense in depth: every query on this model is auto-scoped to
        // the authenticated user. Bypassable via withoutGlobalScope() in
        // jobs/console/admin code that legitimately needs cross-user access.
        static::addGlobalScope(new class implements Scope {
            public function apply(Builder $builder, Model $model): void
            {
                if (Auth::hasUser()) {
                    $builder->where($model->qualifyColumn('user_id'), Auth::id());
                }
            }
        });

        // Auto-assign user_id on create when authenticated and not set.
        static::creating(function (Model $model) {
            if (Auth::hasUser() && empty($model->user_id)) {
                $model->user_id = Auth::id();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }
}

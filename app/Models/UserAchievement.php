<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUser;
use Illuminate\Database\Eloquent\Model;

/**
 * Medalla desbloqueada por un usuario. El catálogo (emoji, título, cómo se gana)
 * vive en App\Support\Gamification::medals(); aquí sólo guardamos qué desbloqueó
 * cada quién y cuándo. BelongsToUser auto-scopea y auto-asigna user_id.
 */
class UserAchievement extends Model
{
    use BelongsToUser;

    protected $fillable = [
        'user_id',
        'key',
        'unlocked_at',
    ];

    protected $casts = [
        'unlocked_at' => 'datetime',
    ];
}

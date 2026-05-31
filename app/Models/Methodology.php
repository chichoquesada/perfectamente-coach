<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Methodology extends Model
{
    // OJO: no usa el trait BelongsToUser a propósito. Ese trait auto-scopea
    // todas las queries a Auth::id(), lo que impediría leer las metodologías
    // globales (user_id = null). Acá el user_id se setea explícito.
    protected $fillable = [
        'user_id',
        'name',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

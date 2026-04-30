<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUser;
use Illuminate\Database\Eloquent\Model;

class DailyMode extends Model
{
    use BelongsToUser;

    protected $fillable = [
        'user_id',
        'date',
        'mode',
    ];

    protected $casts = [
        'date' => 'date',
    ];
}

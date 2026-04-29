<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailyMode extends Model
{
    protected $fillable = [
        'user_id',
        'date',
        'mode',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

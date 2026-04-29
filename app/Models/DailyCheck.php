<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailyCheck extends Model
{
    protected $fillable = [
        'user_id',
        'nutritional_plan_id',
        'date',
        'item_id',
        'status',
        'note',
        'mode',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function plan()
    {
        return $this->belongsTo(NutritionalPlan::class, 'nutritional_plan_id');
    }
}

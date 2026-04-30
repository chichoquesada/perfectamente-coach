<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUser;
use Illuminate\Database\Eloquent\Model;

class DailyCheck extends Model
{
    use BelongsToUser;

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

    public function plan()
    {
        return $this->belongsTo(NutritionalPlan::class, 'nutritional_plan_id');
    }
}

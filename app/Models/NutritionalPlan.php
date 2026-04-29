<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NutritionalPlan extends Model
{
    protected $fillable = [
        'user_id',
        'pdf_path',
        'raw_text',
        'extracted_data',
        'metodologia',
        'objetivo_principal',
        'is_active',
    ];

    protected $casts = [
        'extracted_data' => 'array',
        'is_active' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function checks()
    {
        return $this->hasMany(DailyCheck::class);
    }
}

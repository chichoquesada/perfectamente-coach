<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUser;
use Illuminate\Database\Eloquent\Model;

class NutritionalPlan extends Model
{
    use BelongsToUser;

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

    public function checks()
    {
        return $this->hasMany(DailyCheck::class);
    }
}

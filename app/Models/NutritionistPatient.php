<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NutritionistPatient extends Model
{
    protected $table = 'nutritionist_patient';

    protected $fillable = [
        'nutritionist_id',
        'patient_id',
        'status',
        'invitation_token',
        'invitation_email',
        'invited_at',
        'accepted_at',
        'archived_at',
    ];

    protected function casts(): array
    {
        return [
            'invited_at' => 'datetime',
            'accepted_at' => 'datetime',
            'archived_at' => 'datetime',
        ];
    }

    public function nutritionist(): BelongsTo
    {
        return $this->belongsTo(User::class, 'nutritionist_id');
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}

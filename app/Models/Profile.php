<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Profile extends Model
{
    protected $fillable = [
        'user_id',
        'nombre',
        'plan_tier',
        'stripe_customer_id',
        'stripe_subscription_id',
        'trial_ends_at',
        'affiliate_code',
        'referred_by_code',
        'calendario_entreno',
    ];

    protected $casts = [
        'calendario_entreno' => 'array',
        'trial_ends_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function isPro(): bool
    {
        return $this->plan_tier === 'pro';
    }
}

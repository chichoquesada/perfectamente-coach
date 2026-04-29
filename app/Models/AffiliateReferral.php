<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AffiliateReferral extends Model
{
    protected $fillable = [
        'affiliate_user_id',
        'referred_user_id',
        'status',
        'total_earned_usd',
    ];

    protected $casts = [
        'total_earned_usd' => 'decimal:2',
    ];

    public function affiliate()
    {
        return $this->belongsTo(User::class, 'affiliate_user_id');
    }

    public function referred()
    {
        return $this->belongsTo(User::class, 'referred_user_id');
    }
}

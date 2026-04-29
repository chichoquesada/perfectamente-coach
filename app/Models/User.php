<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function profile()
    {
        return $this->hasOne(Profile::class);
    }

    public function nutritionalPlans()
    {
        return $this->hasMany(NutritionalPlan::class);
    }

    public function activeNutritionalPlan()
    {
        return $this->hasOne(NutritionalPlan::class)->where('is_active', true);
    }

    public function dailyChecks()
    {
        return $this->hasMany(DailyCheck::class);
    }

    public function dailyModes()
    {
        return $this->hasMany(DailyMode::class);
    }

    public function referralsMade()
    {
        return $this->hasMany(AffiliateReferral::class, 'affiliate_user_id');
    }
}

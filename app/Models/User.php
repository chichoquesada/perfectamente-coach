<?php

namespace App\Models;

use App\Observers\UserObserver;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[ObservedBy([UserObserver::class])]
class User extends Authenticatable implements MustVerifyEmail
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
        'role',
        'gamification_enabled',
        'supplements_affect_fidelity',
        'streak_threshold',
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
            'gamification_enabled' => 'boolean',
            'supplements_affect_fidelity' => 'boolean',
            'streak_threshold' => 'integer',
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

    public function userAchievements()
    {
        return $this->hasMany(UserAchievement::class);
    }

    public function dailyModes()
    {
        return $this->hasMany(DailyMode::class);
    }

    public function referralsMade()
    {
        return $this->hasMany(AffiliateReferral::class, 'affiliate_user_id');
    }

    // ----- Roles -----

    public function isNutritionist(): bool
    {
        return $this->role === 'nutritionist';
    }

    public function isPatient(): bool
    {
        return $this->role === 'patient';
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    // ----- Relación nutri ↔ paciente -----

    /**
     * Pacientes de este nutricionista (todos los estados).
     */
    public function patients()
    {
        return $this->belongsToMany(User::class, 'nutritionist_patient', 'nutritionist_id', 'patient_id')
            ->withPivot(['status', 'invitation_token', 'invitation_email', 'invited_at', 'accepted_at', 'archived_at'])
            ->withTimestamps();
    }

    /**
     * Pacientes activos del nutri.
     */
    public function activePatients()
    {
        return $this->patients()->wherePivot('status', 'active');
    }

    /**
     * Nutricionistas a los que este paciente está vinculado.
     */
    public function nutritionists()
    {
        return $this->belongsToMany(User::class, 'nutritionist_patient', 'patient_id', 'nutritionist_id')
            ->withPivot(['status', 'invited_at', 'accepted_at', 'archived_at'])
            ->withTimestamps();
    }

    /**
     * Nutricionista activo del paciente (si tiene). Hoy: 1:1 activo a la vez.
     */
    public function activeNutritionist()
    {
        return $this->nutritionists()->wherePivot('status', 'active')->first();
    }
}

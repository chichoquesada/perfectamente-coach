<?php

namespace App\Observers;

use App\Models\Profile;
use App\Models\User;
use Illuminate\Support\Str;

class UserObserver
{
    public function created(User $user): void
    {
        // Nutris no llevan Profile de paciente (plan, calendario entreno, etc.).
        if ($user->role === 'nutritionist') {
            return;
        }

        Profile::create([
            'user_id' => $user->id,
            'nombre' => $user->name,
            'plan_tier' => 'free',
            'affiliate_code' => Str::lower(Str::random(8)),
            'calendario_entreno' => [
                'lun' => true, 'mar' => true, 'mie' => true, 'jue' => true,
                'vie' => true, 'sab' => true, 'dom' => false,
            ],
        ]);
    }
}

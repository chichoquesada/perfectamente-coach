<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureHasActivePlan
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if ($user) {
            // Nutris no necesitan plan; los mandamos a su panel.
            if ($user->isNutritionist()) {
                return redirect()->route('nutri.dashboard');
            }

            if (! $user->activeNutritionalPlan()->exists()) {
                return redirect()->route('onboarding.show');
            }
        }

        return $next($request);
    }
}

<?php

namespace App\Http\Controllers\Nutri;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class NutriDashboardController extends Controller
{
    public function index(Request $request): View
    {
        $nutri = Auth::user();

        $patients = $nutri->patients()
            ->withPivot(['status', 'invitation_email', 'invited_at', 'accepted_at'])
            ->orderByPivot('status')
            ->orderBy('users.name')
            ->get();

        $counts = [
            'active' => $patients->where('pivot.status', 'active')->count(),
            'invited' => $patients->where('pivot.status', 'invited')->count(),
            'archived' => $patients->where('pivot.status', 'archived')->count(),
        ];

        return view('nutri.dashboard', compact('patients', 'counts'));
    }
}

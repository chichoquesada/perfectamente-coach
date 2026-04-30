<?php

namespace App\Http\Controllers\Nutri;

use App\Http\Controllers\Controller;
use App\Mail\PatientInvitationMail;
use App\Models\NutritionistPatient;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class InvitationController extends Controller
{
    /**
     * Nutri envía una invitación a un paciente por email.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'name' => ['nullable', 'string', 'max:255'],
        ]);

        $nutri = Auth::user();
        $email = strtolower($data['email']);

        // Si ya existe pivot con ese nutri y ese email/paciente, reutilizar.
        $existingUser = User::where('email', $email)->first();

        $pivot = NutritionistPatient::where('nutritionist_id', $nutri->id)
            ->where(function ($q) use ($existingUser, $email) {
                $q->where('invitation_email', $email);
                if ($existingUser) {
                    $q->orWhere('patient_id', $existingUser->id);
                }
            })
            ->first();

        if ($pivot && $pivot->status === 'active') {
            return back()->with('status', 'Ese paciente ya está activo en su cartera.');
        }

        $token = Str::random(48);

        if ($pivot) {
            $pivot->update([
                'invitation_token' => $token,
                'invitation_email' => $email,
                'invited_at' => now(),
                'status' => 'invited',
            ]);
        } else {
            $pivot = NutritionistPatient::create([
                'nutritionist_id' => $nutri->id,
                'patient_id' => $existingUser?->id,
                'status' => 'invited',
                'invitation_token' => $token,
                'invitation_email' => $email,
                'invited_at' => now(),
            ]);
        }

        Mail::to($email)->send(new PatientInvitationMail($nutri, $token, $data['name'] ?? null));

        return back()->with('status', 'Invitación enviada a '.$email);
    }

    /**
     * Vista pública: paciente abre el link del email.
     */
    public function show(string $token): View|RedirectResponse
    {
        $pivot = NutritionistPatient::where('invitation_token', $token)->first();

        if (! $pivot || $pivot->status === 'archived') {
            abort(404);
        }

        if ($pivot->status === 'active') {
            return redirect()->route('login')->with('status', 'Esta invitación ya fue aceptada. Inicie sesión.');
        }

        $nutri = $pivot->nutritionist;
        $existingUser = User::where('email', $pivot->invitation_email)->first();

        return view('invitations.accept', [
            'token' => $token,
            'email' => $pivot->invitation_email,
            'nutri' => $nutri,
            'existingUser' => $existingUser,
        ]);
    }

    /**
     * Paciente acepta la invitación: si no tiene cuenta, la crea; si la tiene, debe loguearse.
     */
    public function accept(Request $request, string $token): RedirectResponse
    {
        $pivot = NutritionistPatient::where('invitation_token', $token)->first();

        if (! $pivot || $pivot->status !== 'invited') {
            abort(404);
        }

        $email = $pivot->invitation_email;
        $user = User::where('email', $email)->first();

        if (! $user) {
            $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'password' => ['required', 'confirmed', Rules\Password::defaults()],
            ]);

            $user = User::create([
                'name' => $request->name,
                'email' => $email,
                'password' => Hash::make($request->password),
                'role' => 'patient',
            ]);

            event(new Registered($user));
            Auth::login($user);
        } elseif (! Auth::check() || Auth::id() !== $user->id) {
            // Existe pero no autenticado: pedir login y volver.
            return redirect()->route('login')->with('status', 'Inicie sesión para aceptar la invitación de su nutricionista.');
        }

        $pivot->update([
            'patient_id' => $user->id,
            'status' => 'active',
            'accepted_at' => now(),
            'invitation_token' => null,
        ]);

        return redirect()->route('dashboard')->with('status', 'Invitación aceptada. Bienvenido a la cartera de '.$pivot->nutritionist->name.'.');
    }
}

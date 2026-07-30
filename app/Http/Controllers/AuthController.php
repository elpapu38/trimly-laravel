<?php

namespace App\Http\Controllers;

use App\Mail\PasswordResetMail;
use App\Mail\VerifyAccountMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    // ── LOGIN ──────────────────────────────────────────────────
    public function loginForm()
    {
        return view('auth.login', ['pageTitle' => 'Iniciar sesión']);
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ], [], ['email' => 'email', 'password' => 'contraseña']);

        $user = User::where('email', trim($validated['email']))->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            return back()->with('error', 'Email o contraseña incorrectos.')->withInput();
        }

        if ($user->status !== 'active') {
            if ($user->status === 'suspended' && $user->suspended_until && $user->suspended_until->isPast()) {
                $user->update(['status' => 'active', 'suspended_until' => null, 'ban_reason' => null]);
                // sigue el login normalmente
            } elseif ($user->status === 'suspended') {
                $until = $user->suspended_until?->format('d/m/Y \a\l\a\s H:i');
                $reason = $user->ban_reason ? ' Motivo: ' . $user->ban_reason . '.' : '';
                return back()->with('error', "Tu cuenta está suspendida hasta el {$until}.{$reason}");
            } elseif ($user->status === 'banned') {
                $reason = $user->ban_reason ? ' Motivo: ' . $user->ban_reason . '.' : '';
                return back()->with('error', "Tu cuenta ha sido baneada de forma permanente.{$reason} Contactá al soporte si creés que es un error.");
            } else {
                return back()->with('error', 'Tu cuenta no está activa. Contactá al soporte.');
            }
        }

        if (!$user->email_verified) {
            return back()->with('error', 'Debés verificar tu email antes de iniciar sesión. Revisá tu bandeja de entrada.');
        }

        Auth::login($user);
        $request->session()->regenerate();
        $user->update(['last_login' => now()]);

        $intended = $request->session()->pull('url.intended');

        return redirect($intended ?: $this->dashboardUrl($user->role))
            ->with('success', '¡Bienvenido, ' . explode(' ', $user->name)[0] . '!');
    }

    // ── REGISTRO ───────────────────────────────────────────────
    public function registerForm()
    {
        return view('auth.register', ['pageTitle' => 'Crear cuenta']);
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|min:2',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string|max:30',
            'password' => 'required|string|min:8',
            'password_confirm' => 'required|same:password',
            'role' => 'required|in:client,shop_owner',
            'terms' => 'required',
        ], [
            'email.unique' => 'Ya existe una cuenta con ese email.',
            'password_confirm.same' => 'Las contraseñas no coinciden.',
            'terms.required' => 'Debés aceptar los términos y condiciones.',
        ]);

        $token = Str::random(32);

        $user = User::create([
            'name' => strip_tags($validated['name']),
            'email' => strtolower(trim($validated['email'])),
            'phone' => strip_tags($validated['phone'] ?? '') ?: null,
            'password' => $validated['password'], // el cast 'hashed' del modelo lo hashea
            'role' => $validated['role'],
            'email_verified' => false,
            'verify_token' => $token,
            'status' => 'active',
        ]);

        Mail::to($user->email)->send(new VerifyAccountMail($user, $token));

        return redirect('/login')->with('success', '¡Cuenta creada! Revisá tu email para verificar tu cuenta antes de iniciar sesión.');
    }

    // ── VERIFICACIÓN DE EMAIL ──────────────────────────────────
    public function verify(string $token, Request $request)
    {
        $user = User::where('verify_token', $token)->first();

        if (!$user) {
            return redirect('/login')->with('error', 'El enlace de verificación es inválido o ya fue usado.');
        }

        $user->update(['email_verified' => true, 'verify_token' => null]);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect($this->dashboardUrl($user->role))->with('success', '✓ Email verificado correctamente. ¡Bienvenido!');
    }

    // ── RECUPERAR CONTRASEÑA ───────────────────────────────────
    public function forgotForm()
    {
        return view('auth.forgot', ['pageTitle' => 'Recuperar contraseña']);
    }

    public function forgot(Request $request)
    {
        $validated = $request->validate(['email' => 'required|email']);
        $email = strtolower(trim($validated['email']));
        $user = User::where('email', $email)->first();

        // Siempre mismo mensaje, para no filtrar qué emails existen
        if ($user) {
            $token = Str::random(32);
            $user->update(['reset_token' => $token, 'reset_expires' => now()->addHour()]);
            Mail::to($email)->send(new PasswordResetMail($user, $token));
        }

        return redirect('/recuperar')->with('success', 'Si existe una cuenta con ese email, recibirás un enlace para restablecer tu contraseña.');
    }

    public function resetForm(string $token)
    {
        $user = User::where('reset_token', $token)->first();

        if (!$user || $user->reset_expires < now()) {
            return redirect('/recuperar')->with('error', 'El enlace expiró o es inválido. Solicitá uno nuevo.');
        }

        return view('auth.reset', ['pageTitle' => 'Nueva contraseña', 'token' => $token]);
    }

    public function reset(string $token, Request $request)
    {
        $user = User::where('reset_token', $token)->first();

        if (!$user || $user->reset_expires < now()) {
            return redirect('/recuperar')->with('error', 'El enlace expiró. Solicitá uno nuevo.');
        }

        $validated = $request->validate([
            'password' => 'required|string|min:8',
            'password_confirm' => 'required|same:password',
        ], ['password_confirm.same' => 'Las contraseñas no coinciden.']);

        $user->update([
            'password' => $validated['password'],
            'reset_token' => null,
            'reset_expires' => null,
        ]);

        return redirect('/login')->with('success', '¡Contraseña actualizada! Ya podés iniciar sesión.');
    }

    // ── LOGOUT ─────────────────────────────────────────────────
    public function logout(Request $request)
    {
        $name = $request->user()->name ?? '';
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/')->with('success', 'Hasta pronto, ' . explode(' ', $name)[0] . '.');
    }

    private function dashboardUrl(string $role): string
    {
        return match ($role) {
            'superadmin' => '/admin',
            'shop_owner' => '/panel',
            'employee' => '/mi-panel',
            default => '/mis-turnos',
        };
    }
}

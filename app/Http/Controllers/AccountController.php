<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Employee;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AccountController extends Controller
{
    // ── Cambiar contraseña ────────────────────────────────────
    public function changePasswordForm(Request $request)
    {
        return view('account.change_password', ['pageTitle' => 'Cambiar contraseña', 'user' => $request->user()]);
    }

    public function changePassword(Request $request)
    {
        $user = $request->user();
        $validated = $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|string|min:8',
            'confirm_password' => 'required|same:new_password',
        ], ['confirm_password.same' => 'Las contraseñas no coinciden.']);

        if (!Hash::check($validated['current_password'], $user->password)) {
            return redirect('/cuenta/contrasena')->with('error', 'La contraseña actual es incorrecta.');
        }

        $user->update(['password' => $validated['new_password']]);

        return redirect($this->backUrl($user->role))->with('success', 'Contraseña actualizada correctamente.');
    }

    // ── Cambiar email ──────────────────────────────────────────
    public function changeEmailForm(Request $request)
    {
        return view('account.change_email', ['pageTitle' => 'Cambiar email', 'user' => $request->user()]);
    }

    public function changeEmail(Request $request)
    {
        $user = $request->user();
        $validated = $request->validate(['new_email' => 'required|email', 'password' => 'required']);

        $newEmail = strtolower(trim($validated['new_email']));

        if (!Hash::check($validated['password'], $user->password)) {
            return redirect('/cuenta/email')->with('error', 'Contraseña incorrecta.');
        }
        if (User::where('email', $newEmail)->where('id', '!=', $user->id)->exists()) {
            return redirect('/cuenta/email')->with('error', 'Ese email ya está en uso.');
        }

        $user->update(['email' => $newEmail]);

        return redirect($this->backUrl($user->role))->with('success', 'Email actualizado correctamente.');
    }

    // ── Eliminar cuenta ─────────────────────────────────────────
    public function deleteAccountForm(Request $request)
    {
        $user = $request->user();
        if ($user->role === 'superadmin') return redirect('/');

        return view('account.delete_account', ['pageTitle' => 'Eliminar cuenta', 'user' => $user]);
    }

    public function deleteAccount(Request $request)
    {
        $user = $request->user();
        if ($user->role === 'superadmin') return redirect('/');

        $validated = $request->validate(['password' => 'required']);
        if (!Hash::check($validated['password'], $user->password)) {
            return redirect('/cuenta/eliminar')->with('error', 'Contraseña incorrecta.');
        }

        $email = $user->email;

        Appointment::where('client_email', $email)->where('date', '>=', now()->toDateString())
            ->whereIn('status', ['pending', 'confirmed'])->update(['status' => 'cancelled_client']);

        if ($user->role === 'employee') {
            Employee::where('user_id', $user->id)->update(['user_id' => null]);
        }
        if ($user->role === 'shop_owner') {
            Shop::where('owner_id', $user->id)->update(['status' => 'closed']);
        }

        $user->update([
            'name' => '[eliminado]', 'email' => "deleted_{$user->id}@trimly.local",
            'phone' => null, 'avatar' => null, 'password' => \Illuminate\Support\Str::random(32),
            'status' => 'banned', 'ban_reason' => 'Cuenta eliminada por el propio usuario',
        ]);

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    private function backUrl(string $role): string
    {
        return match ($role) {
            'superadmin' => '/admin',
            'shop_owner' => '/panel',
            'employee' => '/mi-panel',
            default => '/mis-turnos',
        };
    }
}

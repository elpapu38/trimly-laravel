<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Chequea baneos/suspensiones vigentes en cada request autenticado.
 * Las suspensiones vencidas se levantan automáticamente (igual que el sistema original).
 */
class CheckAccountStatus
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user) {
            if ($user->status === 'suspended' && $user->suspended_until && $user->suspended_until->isPast()) {
                $user->update(['status' => 'active', 'suspended_until' => null, 'ban_reason' => null]);
            } elseif ($user->status === 'banned') {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                abort(403, 'Tu cuenta fue suspendida: ' . ($user->ban_reason ?? 'contactá al soporte.'));
            } elseif ($user->status === 'suspended') {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                $until = $user->suspended_until?->format('d/m/Y H:i');
                abort(403, "Tu cuenta está suspendida hasta {$until}. Motivo: " . ($user->ban_reason ?? '—'));
            }
        }

        return $next($request);
    }
}

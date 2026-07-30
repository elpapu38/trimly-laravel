<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Equivalente al middleware 'client_only' original: permite reservar turnos
 * a invitados (no logueados) y a clientes, pero bloquea cuentas de
 * negocio/empleado/admin.
 */
class EnsureClientOnly
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && !$user->isClient()) {
            $request->session()->flash('error', 'Esta cuenta no puede reservar turnos.');
            return redirect('/');
        }

        return $next($request);
    }
}

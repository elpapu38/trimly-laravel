<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Reemplaza el chequeo de rol que antes hacía BaseController::requireAuth($roles).
 * Uso en rutas: ->middleware('role:shop_owner') o ->middleware('role:shop_owner,superadmin')
 */
class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            $request->session()->flash('error', 'Debés iniciar sesión.');
            return redirect('/login');
        }

        if ($roles && !in_array($user->role, $roles, true)) {
            abort(403, 'Acceso denegado');
        }

        return $next($request);
    }
}

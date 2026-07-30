<?php

use App\Http\Middleware\CheckAccountStatus;
use App\Http\Middleware\EnsureClientOnly;
use App\Http\Middleware\EnsureRole;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Se corre en cada request autenticado: levanta suspensiones vencidas
        // y cierra la sesión si el usuario está baneado/suspendido.
        $middleware->appendToGroup('web', CheckAccountStatus::class);

        $middleware->alias([
            'role' => EnsureRole::class,
            'client_only' => EnsureClientOnly::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e, $request) {
            if (! $request->expectsJson()) {
                return response()->view('errors.404', [], 404);
            }
        });
        $exceptions->render(function (\Illuminate\Auth\Access\AuthorizationException $e, $request) {
            if (! $request->expectsJson()) {
                return response()->view('errors.403', [], 403);
            }
        });
    })->create();

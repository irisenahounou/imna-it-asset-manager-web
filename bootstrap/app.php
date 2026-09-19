<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use App\Http\Middleware\CheckRole;
use App\Http\Middleware\EnsureSingleSession;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
       health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
        $middleware->alias([
            'role' => CheckRole::class,
            ]);
            // Vérifie à chaque requête Web que la session est toujours la session active de l'utilisateur
        $middleware->web(append: [EnsureSingleSession::class]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckRole
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        // Le responsable a un accès transversal d'administration
        if ($user->role === 'RESPONSABLE') {
            return $next($request);
        }

        // Si l'utilisateur possède l'un des rôles autorisés
        if (in_array($user->role, $roles)) {
            return $next($request);
        }

        abort(403, 'Accès non autorisé à cet espace.');
    }
}
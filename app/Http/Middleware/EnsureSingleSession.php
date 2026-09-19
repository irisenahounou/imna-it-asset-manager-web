<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\AuditLog;

class EnsureSingleSession
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check()) {
            $user = Auth::user();
            $currentSessionId = $request->session()->getId();

            if ($user->current_session_id && $user->current_session_id !== $currentSessionId) {
                AuditLog::create([
                    'utilisateur_id' => $user->id,
                    'action' => 'SESSION_INVALIDEE',
                    'description' => 'Session fermée automatiquement suite à une connexion depuis un autre poste.',
                    'adresse_ip' => $request->ip(),
                ]);

                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')->withErrors([
                    'email' => 'Votre session a été fermée car ce compte a été utilisé pour se connecter depuis un autre poste.',
                ]);
            }
        }

        return $next($request);
    }
}
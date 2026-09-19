<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter; // <-- Ajout du RateLimiter
use Illuminate\Support\Str;
use App\Models\AuditLog;
use App\Models\Utilisateur;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'mot_de_passe' => 'required',
        ]);

        // Clé unique pour identifier les tentatives par adresse IP et email
        $throttleKey = Str::transliterate(Str::lower($request->input('email')).'|'.$request->ip());

        // 1. Vérification : si plus de 7 tentatives échouées, on bloque pendant 15 minutes
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            $minutes = ceil($seconds / 60);

            // Audit Log : Tentative de brute-force bloquée
            AuditLog::create([
                'utilisateur_id' => null,
                'action' => 'COMPTE_BLOQUE',
                'description' => "Blocage de sécurité : 7 tentatives échouées pour {$credentials['email']}.",
                'adresse_ip' => $request->ip(),
            ]);

            return back()->withErrors([
                'email' => "Trop de tentatives de connexion échouées (maximum 5). Votre accès est temporairement bloqué pendant {$minutes} minute(s)."
            ]);
        }

        $user = Utilisateur::where('email', $credentials['email'])->first();

        // 2. Vérification des identifiants
        if ($user && Auth::validate(['email' => $credentials['email'], 'password' => $credentials['mot_de_passe'], 'statut_compte' => 1])) {
            
            // Succès : Réinitialisation du compteur de tentatives pour cet utilisateur/IP
            RateLimiter::clear($throttleKey);

            // Génération d'un OTP dynamique à 6 chiffres
            $otpCode = (string) random_int(100000, 999999);

            // Stockage temporaire en session
            $request->session()->put('2fa:user_id', $user->id);
            $request->session()->put('2fa:otp_code', $otpCode);

            // Écriture dans le log Laravel
            Log::info("=== CODE OTP 2FA ===");
            Log::info("Utilisateur : {$user->email}");
            Log::info("Code OTP : {$otpCode}");
            Log::info("====================");

            // Journalisation d'audit
            AuditLog::create([
                'utilisateur_id' => $user->id,
                'action' => '2FA_OTP_GENERATED',
                'description' => "Code de sécurité 2FA généré : {$otpCode}",
                'adresse_ip' => $request->ip(),
            ]);

            return redirect()->route('2fa.show');
        }

        // Échec : On incrémente le compteur de tentatives de 1 (expire après 15 min / 900 s)
        RateLimiter::hit($throttleKey, 900);

        // Audit Log : Échec d'authentification
        AuditLog::create([
            'utilisateur_id' => $user ? $user->id : null,
            'action' => 'ECHEC_CONNEXION',
            'description' => "Tentative de connexion échouée pour {$credentials['email']}.",
            'adresse_ip' => $request->ip(),
        ]);

        return back()->withErrors(['email' => 'Identifiants invalides ou compte inactivé.']);
    }

    public function show2fa()
    {
        if (!session()->has('2fa:user_id')) {
            return redirect()->route('login');
        }
        return view('auth.two-factor');
    }

    public function verify2fa(Request $request)
{
    $request->validate([
        'code_2fa' => 'required|numeric',
    ]);

    $userId = session()->get('2fa:user_id');
    $expectedOtp = session()->get('2fa:otp_code');

    if (!$userId || !$expectedOtp) {
        return redirect()->route('login');
    }

    if ($request->code_2fa === $expectedOtp) {
        $user = Utilisateur::findOrFail($userId);
        Auth::login($user);

        $request->session()->forget(['2fa:user_id', '2fa:otp_code']);
        $request->session()->regenerate();

        // Invalidation de la session précédente : on enregistre la nouvelle session comme seule valide
        $user->update(['current_session_id' => $request->session()->getId()]);

        AuditLog::create([
            'utilisateur_id' => $user->id,
            'action' => 'CONNEXION_2FA',
            'description' => 'Validation 2FA réussie.',
            'adresse_ip' => $request->ip(),
        ]);

        if ($user->role === 'RESPONSABLE') {
            return redirect()->route('responsable.dashboard');
        } elseif ($user->role === 'TECHNICIEN') {
            return redirect()->route('technicien.dashboard');
        } else {
            return redirect()->route('employe.dashboard');
        }
    }

    return back()->withErrors(['code_2fa' => 'Code OTP incorrect.']);
}

    public function logout(Request $request)
    {
        if (Auth::check()) {
            AuditLog::create([
                'utilisateur_id' => Auth::id(),
                'action' => 'DECONNEXION',
                'description' => 'Déconnexion de la session Web.',
                'adresse_ip' => $request->ip(),
            ]);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}

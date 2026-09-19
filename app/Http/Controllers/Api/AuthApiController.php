<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Utilisateur;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AuthApiController extends Controller
{
    /**
     * Étape 1 : vérifie email/mot de passe, génère un OTP (même principe que le Web).
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'mot_de_passe' => 'required',
            'device_name' => 'required|string',
        ]);

        $user = Utilisateur::where('email', $credentials['email'])->first();

        if (!$user || !Auth::validate([
            'email' => $credentials['email'],
            'password' => $credentials['mot_de_passe'],
            'statut_compte' => 1,
        ])) {
            AuditLog::create([
                'utilisateur_id' => $user->id ?? null,
                'action' => 'ECHEC_CONNEXION_MOBILE',
                'description' => "Tentative de connexion mobile échouée pour {$credentials['email']}.",
                'adresse_ip' => $request->ip(),
            ]);

            return response()->json(['message' => 'Identifiants invalides ou compte inactivé.'], 401);
        }

        if (!in_array($user->role, ['EMPLOYE', 'TECHNICIEN'])) {
            return response()->json(['message' => "Ce compte n'est pas autorisé sur l'application mobile."], 403);
        }

        $otpCode = (string) random_int(100000, 999999);
        Cache::put('2fa_otp_mobile_'.$user->id, $otpCode, now()->addMinutes(5));

        Log::info("=== CODE OTP 2FA MOBILE ===");
        Log::info("Utilisateur : {$user->email}");
        Log::info("Code OTP : {$otpCode}");
        Log::info("===========================");

        AuditLog::create([
            'utilisateur_id' => $user->id,
            'action' => '2FA_OTP_GENERATED_MOBILE',
            'description' => "Code de sécurité 2FA mobile généré : {$otpCode}",
            'adresse_ip' => $request->ip(),
        ]);

        return response()->json([
            'message' => 'Code de vérification envoyé.',
            'user_id' => $user->id,
        ]);
    }

    /**
     * Étape 2 : valide l'OTP et délivre le token Sanctum du terminal.
     */
    public function verify2fa(Request $request)
    {
        $data = $request->validate([
            'user_id' => 'required|exists:utilisateurs,id',
            'code_2fa' => 'required|numeric',
            'device_name' => 'required|string',
        ]);

        $expectedOtp = Cache::get('2fa_otp_mobile_'.$data['user_id']);

        if (!$expectedOtp || (string) $data['code_2fa'] !== (string) $expectedOtp) {
            return response()->json(['message' => 'Code OTP incorrect ou expiré.'], 401);
        }

        Cache::forget('2fa_otp_mobile_'.$data['user_id']);

        $user = Utilisateur::findOrFail($data['user_id']);
        $token = $user->createToken($data['device_name'])->plainTextToken;

        AuditLog::create([
            'utilisateur_id' => $user->id,
            'action' => 'CONNEXION_MOBILE',
            'description' => "Connexion mobile réussie depuis « {$data['device_name']} ».",
            'adresse_ip' => $request->ip(),
        ]);

        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'nom' => $user->nom,
                'prenom' => $user->prenom,
                'role' => $user->role,
                'disponibilite' => $user->disponibilite,
            ],
        ]);
    }

    public function me(Request $request)
    {
        return response()->json($request->user());
    }

    /**
     * Révoque uniquement le token du terminal courant (pas tous les terminaux).
     */
    public function logout(Request $request)
    {
        AuditLog::create([
            'utilisateur_id' => $request->user()->id,
            'action' => 'DECONNEXION_MOBILE',
            'description' => 'Révocation du token mobile du terminal courant.',
            'adresse_ip' => $request->ip(),
        ]);

        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Déconnecté.']);
    }
}
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Panne;
use App\Models\Materiel;
use App\Models\Utilisateur;
use App\Models\AuditLog;
use App\Notifications\PanneDeclareeNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;

class EmployeApiController extends Controller
{
    /**
     * Liste des matériels disponibles pour déclarer une panne (menu déroulant côté app).
     */
    public function materiels(Request $request)
    {
        $materiels = Materiel::all(['id', 'numero_serie', 'nom_equipement', 'type', 'etat_operationnel']);

        return response()->json($materiels);
    }

    /**
     * Déclaration d'une panne corrective avec photo prise directement (Camera API côté app).
     */
    public function store(Request $request)
    {
        $request->validate([
            'materiel_id' => 'required|exists:materiels,id',
            'titre'       => 'required|string|max:255',
            'description' => 'required|string',
            'photo'       => 'nullable|image|mimes:jpeg,png,jpg|max:5120',
        ]);

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('pannes', 'public');
        }

        $panne = Panne::create([
            'materiel_id' => $request->materiel_id,
            'user_id'     => Auth::id(),
            'type_panne'  => 'CORRECTIVE',
            'titre'       => $request->titre,
            'description' => $request->description,
            'photo'       => $photoPath,
            'statut'      => 'En attente',
        ]);

        $materiel = Materiel::findOrFail($request->materiel_id);
        $materiel->update(['etat_operationnel' => 'En panne']);

        AuditLog::create([
            'utilisateur_id' => Auth::id(),
            'action'         => 'SIGNALEMENT_PANNE_MOBILE',
            'description'    => "Panne corrective « {$panne->titre} » déclarée depuis l'app mobile pour {$materiel->nom_equipement}.",
            'adresse_ip'     => $request->ip(),
        ]);

        // Le Système (SGR) notifie automatiquement les Responsables
        $responsables = Utilisateur::where('role', 'RESPONSABLE')->get();
        Notification::send($responsables, new PanneDeclareeNotification($panne));

        return response()->json([
            'message' => 'Panne signalée avec succès.',
            'panne' => $panne,
        ], 201);
    }

    /**
     * Liste des déclarations de l'employé connecté, avec l'état d'avancement.
     */
    public function mine(Request $request)
    {
        $pannes = Panne::where('user_id', Auth::id())
            ->with('materiel:id,nom_equipement,numero_serie')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($p) {
                return [
                    'id' => $p->id,
                    'titre' => $p->titre,
                    'description' => $p->description,
                    'statut' => $p->statut,
                    'photo_url' => $p->photo ? asset('storage/'.$p->photo) : null,
                    'date_declaration' => $p->date_declaration ?? $p->created_at,
                    'materiel' => $p->materiel,
                ];
            });

        return response()->json($pannes);
    }
}
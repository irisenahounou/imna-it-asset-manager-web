<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Panne;
use App\Models\Materiel;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use App\Models\Utilisateur;
use App\Notifications\PanneDeclareeNotification;
use Illuminate\Support\Facades\Notification;

class EmployeController extends Controller
{
    public function dashboard()
    {
        $employeId = Auth::id();

        $materiels = Materiel::all();

        // Récupération basée sur user_id
        $mesPannes = Panne::where('user_id', $employeId)
            ->with('materiel')
            ->orderBy('date_declaration', 'desc')
            ->get();

        return view('employe.dashboard', compact('materiels', 'mesPannes'));
    }

    public function signalerPanne(Request $request)
    {
        $request->validate([
            'materiel_id' => 'required|exists:materiels,id',
            'titre'       => 'required|string|max:255',
            'description' => 'required|string',
            'photo'       => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('pannes', 'public');
        }

        // Insertion avec l'ID de l'utilisateur connecté
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
            'action'         => 'SIGNALEMENT_PANNE',
            'description'    => "Panne corrective '{$panne->titre}' déclarée pour {$materiel->nom_equipement}.",
            'adresse_ip'     => $request->ip(),
        ]);
        // Le Système (SGR) notifie automatiquement les Responsables
         $responsables = Utilisateur::where('role', 'RESPONSABLE')->get();
         Notification::send($responsables, new PanneDeclareeNotification($panne));

        return redirect()->route('employe.dashboard')->with('success', 'Panne signalée avec succès.');
    }
}
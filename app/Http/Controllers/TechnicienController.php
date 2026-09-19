<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Reparation;
use App\Models\Produit;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use App\Models\Utilisateur;
use App\Notifications\ReparationClotureeNotification;
use App\Notifications\StockCritiqueNotification;
use Illuminate\Support\Facades\Notification;

class TechnicienController extends Controller
{
    /**
     * Affiche le tableau de bord du technicien avec ses interventions séparées.
     */
    public function dashboard()
    {
         $technicien = Auth::user();
         $technicienId = $technicien->id;

        // 1. Réparations affectées et EN COURS (date_fin est null)
        $reparationsEnCours = Reparation::whereHas('techniciens', function($q) use ($technicienId) {
            $q->where('utilisateurs.id', $technicienId);
        })
        ->whereNull('date_fin')
        ->with(['panne.materiel', 'produits'])
        ->get();

        // 2. Historique des réparations CLÔTURÉES (date_fin est renseignée)
        $reparationsCloturees = Reparation::whereHas('techniciens', function($q) use ($technicienId) {
            $q->where('utilisateurs.id', $technicienId);
        })
        ->whereNotNull('date_fin')
        ->with(['panne.materiel', 'produits'])
        ->get();

        // Liste des composants disponibles en stock pour le formulaire
        $produits = Produit::where('quantite_stock', '>', 0)->get();

       return view('technicien.dashboard', compact('technicien', 'reparationsEnCours', 'reparationsCloturees', 'produits'));
    }
    public function toggleDisponibilite(Request $request)
{
    $technicien = Auth::user();
    $technicien->update(['disponibilite' => !$technicien->disponibilite]);

    AuditLog::create([
        'utilisateur_id' => $technicien->id,
        'action' => 'CHANGEMENT_DISPONIBILITE',
        'description' => 'Disponibilité passée à ' . ($technicien->disponibilite ? 'Disponible' : 'Occupé') . '.',
        'adresse_ip' => $request->ip(),
    ]);

    return redirect()->route('technicien.dashboard');
}

    /**
     * Clôture une intervention, décrémente les pièces en stock et met à jour le statut du matériel.
     */
    public function cloturerReparation(Request $request, $id)
    {
        $request->validate([
            'rapport_technique' => 'required|string',
            'produits' => 'nullable|array',
            'produits.*.id' => 'nullable|exists:produits,id',
            'produits.*.quantite' => 'nullable|integer|min:1',
        ]);

        $reparation = Reparation::findOrFail($id);

        // Clôture de l'intervention
        $reparation->update([
            'date_fin' => now(),
            'rapport_technique' => $request->rapport_technique,
        ]);

        // Décrémentation du stock et attachement du champ quantite_utilisee en table pivot
        if ($request->has('produits')) {
            foreach ($request->produits as $item) {
                if (!empty($item['id']) && !empty($item['quantite'])) {
                    $produit = Produit::find($item['id']);
                    if ($produit && $produit->quantite_stock >= $item['quantite']) {
                        $produit->decrement('quantite_stock', $item['quantite']);

                        // Attachement à la table pivot avec le nom exact de la colonne
                        $reparation->produits()->attach($item['id'], [
                            'quantite' => $item['quantite'],
                        ]);
                    }
                }
            }
        }

        // Mettre à jour la panne et repasser le matériel en statut Opérationnel
        $panne = $reparation->panne;
        if ($panne) {
            $panne->update(['statut' => 'Clôturée']);
            if ($panne->materiel) {
                $panne->materiel->update(['etat_operationnel' => 'Opérationnel']);
            }
        }

        // Enregistrement dans le journal d'audit
        AuditLog::create([
            'utilisateur_id' => Auth::id(),
            'action' => 'CLOTURE_REPARATION',
            'description' => "Intervention {$reparation->id} clôturée avec succès.",
            'adresse_ip' => $request->ip(),
        ]);
        // Le Système (SGR) notifie automatiquement l'employé qui a déclaré la panne
       if ($panne && $panne->user) {
       $panne->user->notify(new ReparationClotureeNotification($reparation));
}

       // Le Système (SGR) vérifie les seuils critiques et alerte les Responsables
       foreach ($reparation->produits as $produitUtilise) {
       if ($produitUtilise->verifierSeuilCritique()) {
        $responsables = Utilisateur::where('role', 'RESPONSABLE')->get();
        Notification::send($responsables, new StockCritiqueNotification($produitUtilise));
    }
}

        return redirect()->route('technicien.dashboard')->with('success', 'Intervention clôturée. Elle a été transférée dans votre historique.');
    }
}

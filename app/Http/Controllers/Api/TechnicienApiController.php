<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Reparation;
use App\Models\Produit;
use App\Models\Utilisateur;
use App\Models\AuditLog;
use App\Notifications\ReparationClotureeNotification;
use App\Notifications\StockCritiqueNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;

class TechnicienApiController extends Controller
{
    /**
     * Interventions du technicien connecté, séparées en cours / clôturées.
     */
    public function mine(Request $request)
    {
        $technicienId = Auth::id();

        $reparationsEnCours = Reparation::whereHas('techniciens', function ($q) use ($technicienId) {
                $q->where('utilisateurs.id', $technicienId);
            })
            ->whereNull('date_fin')
            ->with(['panne.materiel', 'produits'])
            ->get();

        $reparationsCloturees = Reparation::whereHas('techniciens', function ($q) use ($technicienId) {
                $q->where('utilisateurs.id', $technicienId);
            })
            ->whereNotNull('date_fin')
            ->with(['panne.materiel', 'produits'])
            ->get();

        return response()->json([
            'en_cours' => $reparationsEnCours,
            'cloturees' => $reparationsCloturees,
        ]);
    }
    public function produits(Request $request)
   {

    $produits = Produit::select('id_composant', 'nom_composant', 'type_composant', 'quantite_stock')->get();
    return response()->json($produits);
    
  }

    /**
     * Bascule la disponibilité (Disponible / Occupé) — utilisée au pied de l'équipement.
     */
    public function toggleDisponibilite(Request $request)
    {
        $technicien = Auth::user();
        $technicien->update(['disponibilite' => !$technicien->disponibilite]);

        AuditLog::create([
            'utilisateur_id' => $technicien->id,
            'action' => 'CHANGEMENT_DISPONIBILITE',
            'description' => 'Disponibilité (mobile) passée à ' . ($technicien->disponibilite ? 'Disponible' : 'Occupé') . '.',
            'adresse_ip' => $request->ip(),
        ]);

        return response()->json(['disponibilite' => $technicien->disponibilite]);
    }

    /**
     * Clôture une intervention depuis le terrain : rapport technique + pièces consommées.
     */
    public function cloturer(Request $request, $id)
    {
        $request->validate([
            'rapport_technique' => 'required|string',
            'produits' => 'nullable|array',
            'produits.*.id' => 'nullable|exists:produits,id',
            'produits.*.quantite' => 'nullable|integer|min:1',
        ]);

        $reparation = Reparation::findOrFail($id);

        $reparation->update([
            'date_fin' => now(),
            'rapport_technique' => $request->rapport_technique,
        ]);

        $produitsCritiques = [];

        if ($request->has('produits')) {
            foreach ($request->produits as $item) {
                if (!empty($item['id']) && !empty($item['quantite'])) {
                    $produit = Produit::find($item['id']);
                    if ($produit && $produit->quantite_stock >= $item['quantite']) {
                        $produit->decrement('quantite_stock', $item['quantite']);

                        $reparation->produits()->attach($item['id'], [
                            'quantite' => $item['quantite'],
                        ]);

                        if ($produit->verifierSeuilCritique()) {
                            $produitsCritiques[] = $produit;
                        }
                    }
                }
            }
        }

        $panne = $reparation->panne;
        if ($panne) {
            $panne->update(['statut' => 'Clôturée']);
            if ($panne->materiel) {
                $panne->materiel->update(['etat_operationnel' => 'Opérationnel']);
            }
        }

        AuditLog::create([
            'utilisateur_id' => Auth::id(),
            'action' => 'CLOTURE_REPARATION_MOBILE',
            'description' => "Intervention {$reparation->id} clôturée depuis l'app mobile.",
            'adresse_ip' => $request->ip(),
        ]);

        // Le Système (SGR) notifie automatiquement l'employé ayant déclaré la panne
        if ($panne && $panne->user) {
            $panne->user->notify(new ReparationClotureeNotification($reparation));
        }

        // Le Système (SGR) alerte les Responsables si un seuil critique est franchi
        if (!empty($produitsCritiques)) {
            $responsables = Utilisateur::where('role', 'RESPONSABLE')->get();
            foreach ($produitsCritiques as $produit) {
                Notification::send($responsables, new StockCritiqueNotification($produit));
            }
        }

        return response()->json([
            'message' => 'Intervention clôturée avec succès.',
            'reparation' => $reparation->fresh(['panne.materiel', 'produits']),
        ]);
    }
}
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
        $data = $request->validate([
        'rapport_technique' => 'required|string',
        'produits' => 'nullable|array',
        'produits.*.id' => 'nullable|integer|exists:produits,id_composant',
        'produits.*.quantite' => 'nullable|integer|min:1',
    ]);

        $produitsDemandes = collect($data['produits'] ?? [])
        ->filter(fn ($item) => !empty($item['id']) && !empty($item['quantite']));

    $panne = null;

    \Illuminate\Support\Facades\DB::transaction(function () use (
        $id, $data, $produitsDemandes, &$panne, $request
    ) {
        $reparation = Reparation::whereKey($id)->lockForUpdate()->firstOrFail();
        // Décrémentation du stock et attachement du champ quantite_utilisee en table pivot
        if ($reparation->date_fin !== null) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'rapport_technique' => 'Cette réparation est déjà clôturée.',
            ]);
        }
                // Vérifier et verrouiller tous les produits avant toute consommation.
        $produitsVerrouilles = [];

        foreach ($produitsDemandes as $item) {
            $produitId = (int) $item['id'];
            $quantite = (int) $item['quantite'];

            if (isset($produitsVerrouilles[$produitId])) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'produits' => 'Une même pièce ne peut être sélectionnée qu’une fois.',
                ]);
            }

            $produit = Produit::whereKey($produitId)->lockForUpdate()->first();

            if (!$produit) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'produits' => 'Une pièce sélectionnée est introuvable.',
                ]);
            }

            if ($produit->quantite_stock < $quantite) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'produits' => "Stock insuffisant pour {$produit->nom_composant}.",
                ]);
            }

            $produitsVerrouilles[$produitId] = [
                'produit' => $produit,
                'quantite' => $quantite,
            ];
        }

        // Consommer le stock et enregistrer les pièces utilisées.
        foreach ($produitsVerrouilles as $ligne) {
            $ligne['produit']->decrement('quantite_stock', $ligne['quantite']);

            $reparation->produits()->attach(
                $ligne['produit']->getKey(),
                ['quantite' => $ligne['quantite']]
            );
        }

        // Clôturer seulement après validation de toutes les pièces.
        $reparation->update([
            'date_fin' => now(),
            'rapport_technique' => $data['rapport_technique'],
        ]);

        $panne = $reparation->panne()->with('materiel', 'user')->first();

        if ($panne) {
            $panne->update(['statut' => 'Clôturée']);

            if ($panne->materiel) {
                $panne->materiel->update([
                    'etat_operationnel' => 'Opérationnel',
                ]);
            }
        }

        AuditLog::create([
            'utilisateur_id' => Auth::id(),
            'action' => 'CLOTURE_REPARATION',
            'description' => "Intervention {$reparation->id} clôturée avec succès.",
            'adresse_ip' => $request->ip(),
        ]);
    });

    // Les notifications sont envoyées après la validation de la transaction.
    $reparation = Reparation::with('produits')->findOrFail($id);

    if ($panne && $panne->user) {
        $panne->user->notify(new ReparationClotureeNotification($reparation));
    }

    $responsables = Utilisateur::where('role', 'RESPONSABLE')->get();

    foreach ($reparation->produits as $produitUtilise) {
        if ($produitUtilise->verifierSeuilCritique() && $responsables->isNotEmpty()) {
            Notification::send(
                $responsables,
                new StockCritiqueNotification($produitUtilise)
            );
        }
    }

    return redirect()
        ->route('technicien.dashboard')
        ->with('success', 'Intervention clôturée. Elle a été transférée dans votre historique.');
    }
}

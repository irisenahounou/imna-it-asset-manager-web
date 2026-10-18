<?php

namespace Tests\Feature;

use App\Models\Materiel;
use App\Models\Panne;
use App\Models\Produit;
use App\Models\Reparation;
use App\Models\Utilisateur;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClotureReparationStockInsuffisantTest extends TestCase
{
    use RefreshDatabase;

    public function test_reparation_ne_peut_pas_etre_cloturee_si_le_stock_est_insuffisant(): void
    {
        // Créer un technicien de test.
        $technicien = Utilisateur::create([
            'nom' => 'Test',
            'prenom' => 'Technicien',
            'email' => 'technicien-stock-test@imna-test.local',
            'mot_de_passe' => bcrypt('Password123!'),
            'statut_compte' => true,
            'role' => 'TECHNICIEN',
            'disponibilite' => true,
        ]);

        // Créer le matériel concerné par la panne.
        $materiel = Materiel::create([
            'numero_serie' => 'SERIE-TEST-STOCK-001',
            'nom_equipement' => 'Ordinateur de test',
            'type' => 'Informatique',
            'date_achat' => '2025-01-01',
            'etat_operationnel' => 'En panne',
        ]);

        // Créer la panne.
        $panne = Panne::create([
            'materiel_id' => $materiel->id,
            'type_panne' => 'CORRECTIVE',
            'titre' => 'Panne de test pour stock insuffisant',
            'description' => 'Test automatisé',
            'statut' => 'En attente',
        ]);

        // Créer une réparation encore ouverte.
        $reparation = Reparation::create([
            'id' => 'REP-TEST-STOCK-001',
            'date_debut' => now(),
            'date_fin' => null,
            'rapport_technique' => null,
            'panne_id' => $panne->id,
        ]);

        // Associer le technicien à la réparation.
        $reparation->techniciens()->attach($technicien->id);

        // Créer une pièce avec seulement 2 unités en stock.
        $produit = Produit::create([
            'nom_composant' => 'Filtre de ventilation de test',
            'type_composant' => 'Pièce de rechange',
            'quantite_stock' => 2,
            'seuil_alerte' => 0,
        ]);

        // Le technicien demande 3 unités : la clôture doit être refusée.
        $response = $this->actingAs($technicien)->post(
            route('technicien.cloturer', ['id' => $reparation->id]),
            [
                'rapport_technique' => 'Test de stock insuffisant',
                'produits' => [
                    [
                        'id' => $produit->id_composant,
                        'quantite' => 3,
                    ],
                ],
            ]
        );

        // Vérifier que la validation refuse la demande.
        $response->assertSessionHasErrors('produits');

        // Vérifier que le stock n'a pas diminué.
        $this->assertDatabaseHas('produits', [
            'id_composant' => $produit->id_composant,
            'quantite_stock' => 2,
        ]);

        // Vérifier que la réparation reste ouverte.
        $this->assertDatabaseHas('reparations', [
            'id' => $reparation->id,
            'date_fin' => null,
        ]);

        // Vérifier qu'aucune pièce n'a été enregistrée.
        $this->assertDatabaseMissing('details_reparation', [
            'reparation_id' => $reparation->id,
            'produit_id' => $produit->id_composant,
        ]);
    }
}

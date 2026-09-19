<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Insertion des Utilisateurs (1 Responsable, 2 Techniciens, 1 Employé)
        DB::table('utilisateurs')->insert([
            [
                'id' => 1,
                'nom' => 'Nahounou',
                'prenom' => 'Irise',
                'email' => 'responsable@entreprise.com',
                'mot_de_passe' => Hash::make('Password123!'),
                'statut_compte' => true,
                'role' => 'RESPONSABLE',
                'disponibilite' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'nom' => 'Martin',
                'prenom' => 'Sophie',
                'email' => 'sophie.martin@entreprise.com',
                'mot_de_passe' => Hash::make('Password123!'),
                'statut_compte' => true,
                'role' => 'TECHNICIEN',
                'disponibilite' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 3,
                'nom' => 'Benoit',
                'prenom' => 'Pierre',
                'email' => 'pierre.benoit@entreprise.com',
                'mot_de_passe' => Hash::make('Password123!'),
                'statut_compte' => true,
                'role' => 'TECHNICIEN',
                'disponibilite' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 4,
                'nom' => 'Dupont',
                'prenom' => 'Jean',
                'email' => 'jean.dupont@entreprise.com',
                'mot_de_passe' => Hash::make('Password123!'),
                'statut_compte' => true,
                'role' => 'EMPLOYE',
                'disponibilite' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // 2. Insertion du Parc Matériel
        DB::table('materiels')->insert([
            [
                'id' => 1,
                'numero_serie' => 'SN-DRONE-001',
                'nom_equipement' => 'DJI Matrice 350 RTK',
                'type' => 'Drone',
                'date_achat' => '2025-03-15',
                'etat_operationnel' => 'En service',
            ],
            [
                'id' => 2,
                'numero_serie' => 'SN-AGRAS-002',
                'nom_equipement' => 'DJI Agras T40',
                'type' => 'Drone',
                'date_achat' => '2025-06-20',
                'etat_operationnel' => 'En panne',
            ],
            [
                'id' => 3,
                'numero_serie' => 'SN-PLC-003',
                'nom_equipement' => 'Automate Siemens S7-1200',
                'type' => 'Automate',
                'date_achat' => '2024-11-10',
                'etat_operationnel' => 'En service',
            ],
            [
                'id' => 4,
                'numero_serie' => 'SN-SRV-004',
                'nom_equipement' => 'Serveur Dell PowerEdge',
                'type' => 'Serveur',
                'date_achat' => '2025-01-10',
                'etat_operationnel' => 'En service',
            ],
        ]);

        // 3. Insertion des Pannes (Correctives et Préventives)
        DB::table('pannes')->insert([
            [
                'id' => 1,
                'titre' => 'Surchauffe moteur arrière gauche',
                'description' => 'Le moteur chauffe anormalement après 10 min de vol.',
                'date_declaration' => '2026-09-10 09:30:00',
                'statut' => 'En cours',
                'photo' => null,
                'type_panne' => 'CORRECTIVE',
                'frequence_jour' => null,
                'prochaine_reparation' => null,
                'materiel_id' => 2,
            ],
            [
                'id' => 2,
                'titre' => 'Inspection trimestrielle des capteurs',
                'description' => 'Maintenance préventive programmée pour calibration.',
                'date_declaration' => '2026-09-01 08:00:00',
                'statut' => 'En attente',
                'photo' => null,
                'type_panne' => 'PREVENTIVE',
                'frequence_jour' => 90,
                'prochaine_reparation' => '2026-12-01',
                'materiel_id' => 1,
            ],
        ]);

        // 4. Insertion d'une Réparation
        DB::table('reparations')->insert([
            [
                'id' => 'REP-2026-001',
                'date_debut' => '2026-09-12 10:00:00',
                'date_fin' => null,
                'rapport_technique' => 'Diagnostic en cours : vérification de l alim et du moteur.',
                'panne_id' => 1,
            ],
        ]);

        // 5. Affectation Technicien <-> Réparation
        DB::table('reparation_technicien')->insert([
            [
                'reparation_id' => 'REP-2026-001',
                'technicien_id' => 2,
            ],
        ]);

        // 6. Insertion des Produits / Composants (Stock)
        DB::table('produits')->insert([
            [
                'id_composant' => 1,
                'nom_composant' => 'Moteur Brushless M350',
                'type_composant' => 'Moteur',
                'quantite_stock' => 5,
                'seuil_alerte' => 2,
            ],
            [
                'id_composant' => 2,
                'nom_composant' => 'Hélice Carbone 21 pouces',
                'type_composant' => 'Accessoire',
                'quantite_stock' => 1, // En alerte !
                'seuil_alerte' => 4,
            ],
            [
                'id_composant' => 3,
                'nom_composant' => 'Module Alimentation S7-1200',
                'type_composant' => 'Électronique',
                'quantite_stock' => 3,
                'seuil_alerte' => 1,
            ],
            [
                'id_composant' => 4,
                'nom_composant' => 'Disque SSD NVMe 1To',
                'type_composant' => 'Stockage',
                'quantite_stock' => 10,
                'seuil_alerte' => 3,
            ],
        ]);

        // 7. Détails de consommation de pièces lors des réparations
        DB::table('details_reparation')->insert([
            [
                'id' => 1,
                'reparation_id' => 'REP-2026-001',
                'produit_id' => 1,
                'quantite' => 1,
                'commentaire' => 'Remplacement du bloc moteur défectueux.',
            ],
        ]);

        // 8. Journal d'Audit initial
        DB::table('audit_logs')->insert([
            [
                'id' => 1,
                'utilisateur_id' => 1,
                'action' => 'INITIALISATION',
                'description' => 'Initialisation du système et création des comptes de test.',
                'adresse_ip' => '127.0.0.1',
                'created_at' => now(),
            ],
        ]);
    }
}

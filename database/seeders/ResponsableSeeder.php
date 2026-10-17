<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class ResponsableSeeder extends Seeder
{
    public function run(): void
    {
        $email = 'responsable@entreprise.com';
        $password = env('RESPONSABLE_INITIAL_PASSWORD');

        // Vérifier que le mot de passe initial est configuré
        if (empty($password)) {
            throw new RuntimeException(
                'La variable RESPONSABLE_INITIAL_PASSWORD est absente.'
            );
        }

        // Ne pas créer de doublon si le compte existe déjà
        $utilisateurExiste = DB::table('utilisateurs')
            ->where('email', $email)
            ->exists();

        if ($utilisateurExiste) {
            $this->command?->info(
                'Le compte responsable existe déjà. Aucune modification effectuée.'
            );

            return;
        }

        // Créer uniquement le compte responsable
        DB::table('utilisateurs')->insert([
            'id' => 1,
            'nom' => 'Nahounou',
            'prenom' => 'Irise',
            'email' => $email,
            'mot_de_passe' => Hash::make($password),
            'statut_compte' => true,
            'role' => 'RESPONSABLE',
            'disponibilite' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->command?->info(
            'Le compte responsable a été créé avec succès.'
        );
    }
}

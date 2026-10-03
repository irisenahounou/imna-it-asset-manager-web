<?php

namespace Tests\Feature;

use App\Models\Utilisateur;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccessControlTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $role): Utilisateur
    {
        return Utilisateur::create([
            'nom' => 'Test',
            'prenom' => ucfirst(strtolower($role)),
            'email' => strtolower($role).'@imna-test.local',
            'mot_de_passe' => bcrypt('Password123!'),
            'statut_compte' => true,
            'role' => $role,
            'disponibilite' => true,
        ]);
    }

    /** Un visiteur non authentifié est redirigé vers la connexion. */
    public function test_guest_is_redirected_to_login_from_protected_route(): void
    {
        $response = $this->get('/responsable/dashboard');

        $response->assertRedirect(route('login'));
    }

    /**
     * Régression de la correction de sécurité (chapitre 4.5 du rapport) :
     * un Employé authentifié ne doit jamais pouvoir accéder à l'espace Responsable.
     */
    public function test_employe_cannot_access_responsable_dashboard(): void
    {
        $employe = $this->makeUser('EMPLOYE');

        $response = $this->actingAs($employe)->get('/responsable/dashboard');

        $response->assertStatus(403);
    }

    /** Un Technicien authentifié ne doit pas accéder à l'espace Employé. */
    public function test_technicien_cannot_access_employe_dashboard(): void
    {
        $technicien = $this->makeUser('TECHNICIEN');

        $response = $this->actingAs($technicien)->get('/employe/dashboard');

        $response->assertStatus(403);
    }

    /** Le Responsable dispose d'un accès transversal (comportement attendu, pas une faille). */
    public function test_responsable_has_transversal_access_to_employe_space(): void
    {
        $responsable = $this->makeUser('RESPONSABLE');

        $response = $this->actingAs($responsable)->get('/employe/dashboard');

        $response->assertStatus(200);
    }

    /** Chaque rôle accède bien à son propre espace. */
    public function test_employe_can_access_own_dashboard(): void
    {
        $employe = $this->makeUser('EMPLOYE');

        $response = $this->actingAs($employe)->get('/employe/dashboard');

        $response->assertStatus(200);
    }
}

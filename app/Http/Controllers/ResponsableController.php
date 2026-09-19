<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Materiel;
use App\Models\Panne;
use App\Models\Reparation;
use App\Models\Produit;
use App\Models\Utilisateur;
use App\Models\AuditLog;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Hash;
use App\Notifications\TechnicienAffecteNotification;

class ResponsableController extends Controller
{
    public function dashboard()
    {
        $materiels = Materiel::all();
        
        // 1. Pannes en attente de planification
        $pannesEnAttente = Panne::where('statut', 'En attente')->with('materiel')->get();
        
        // 2. Historique de TOUTES les pannes (pour suivi global)
        $toutesLesPannes = Panne::with('materiel')->orderBy('date_declaration', 'desc')->get();

        // 3. Techniciens disponibles
        $techniciensDispo = Utilisateur::where('role', 'TECHNICIEN')->orderByDesc('disponibilite')->get();
        // 4. Historique de toutes les réparations (y compris clôturées avec rapports)
        $reparations = Reparation::with(['panne.materiel', 'techniciens', 'produits'])->get();

        // 5. Stocks critiques
        $produitsCritiques = Produit::all()->filter(fn($p) => $p->verifierSeuilCritique());
        
        // 6. Journal d'Audit
        $auditLogs = AuditLog::with('utilisateur')->latest()->take(15)->get();
                // 7. Liste des utilisateurs (pour la gestion des accès)
        $utilisateurs = Utilisateur::all();
               // 8. Maintenances préventives programmées (toutes, avec leur statut d'avancement)
        $maintenancesPreventives = Panne::where('type_panne', 'PREVENTIVE')
        ->with('materiel')
        ->orderBy('prochaine_reparation')
        ->get();

        return view('responsable.dashboard', compact(
            'materiels', 
            'pannesEnAttente', 
            'toutesLesPannes',
            'techniciensDispo', 
            'reparations',
            'produitsCritiques', 
            'auditLogs',
            'utilisateurs',
            'maintenancesPreventives'
        ));
       
    }

    public function planifierReparation(Request $request)
    {
        $request->validate([
            'id' => 'required|string|unique:reparations,id',
            'panne_id' => 'required|exists:pannes,id',
            'date_debut' => 'required|date',
            'technicien_id' => 'required|exists:utilisateurs,id',
        ]);

        $reparation = Reparation::create([
            'id' => $request->id,
            'date_debut' => $request->date_debut,
            'panne_id' => $request->panne_id,
        ]);

        $reparation->techniciens()->attach($request->technicien_id);
        // Le Système (SGR) notifie automatiquement le technicien affecté
        Utilisateur::find($request->technicien_id)->notify(new TechnicienAffecteNotification($reparation));

        Panne::find($request->panne_id)->update(['statut' => 'En cours']);

        AuditLog::create([
            'utilisateur_id' => auth()->id(),
            'action' => 'AFFECTATION',
            'description' => "Réparation {$request->id} assignée au technicien ID {$request->technicien_id}.",
            'adresse_ip' => $request->ip(),
        ]);

        return redirect()->back()->with('success', 'Réparation planifiée et technicien affecté.');
    }
    public function storeMateriel(Request $request)
{
    $data = $request->validate([
        'numero_serie' => 'required|string|unique:materiels,numero_serie',
        'nom_equipement' => 'required|string|max:255',
        'type' => 'required|string|max:100',
        'date_achat' => 'required|date',
        'etat_operationnel' => 'required|string|max:100',
    ]);

    Materiel::create($data);

    AuditLog::create([
        'utilisateur_id' => auth()->id(),
        'action' => 'AJOUT_MATERIEL',
        'description' => "Ajout du matériel {$data['nom_equipement']} (SN: {$data['numero_serie']}).",
        'adresse_ip' => $request->ip(),
    ]);

    return redirect()->back()->with('success', 'Matériel ajouté au parc.');
}

public function updateMateriel(Request $request, $id)
{
    $materiel = Materiel::findOrFail($id);

    $data = $request->validate([
        'numero_serie' => 'required|string|unique:materiels,numero_serie,' . $id,
        'nom_equipement' => 'required|string|max:255',
        'type' => 'required|string|max:100',
        'date_achat' => 'required|date',
        'etat_operationnel' => 'required|string|max:100',
    ]);

    $materiel->update($data);

    AuditLog::create([
        'utilisateur_id' => auth()->id(),
        'action' => 'MODIFICATION_MATERIEL',
        'description' => "Modification du matériel #{$materiel->id} ({$materiel->nom_equipement}).",
        'adresse_ip' => $request->ip(),
    ]);

    return redirect()->back()->with('success', 'Matériel mis à jour.');
}

public function destroyMateriel(Request $request, $id)
{
    $materiel = Materiel::findOrFail($id);
    $nom = $materiel->nom_equipement;
    $materiel->delete();

    AuditLog::create([
        'utilisateur_id' => auth()->id(),
        'action' => 'SUPPRESSION_MATERIEL',
        'description' => "Suppression du matériel {$nom}.",
        'adresse_ip' => $request->ip(),
    ]);

    return redirect()->back()->with('success', 'Matériel supprimé.');
}

public function storeProduit(Request $request)
{
    $data = $request->validate([
        'nom_composant' => 'required|string|max:255',
        'type_composant' => 'required|string|max:100',
        'quantite_stock' => 'required|integer|min:0',
        'seuil_alerte' => 'required|integer|min:0',
    ]);

    Produit::create($data);

    AuditLog::create([
        'utilisateur_id' => auth()->id(),
        'action' => 'AJOUT_PRODUIT',
        'description' => "Ajout du produit {$data['nom_composant']} au stock.",
        'adresse_ip' => $request->ip(),
    ]);

    return redirect()->back()->with('success', 'Produit ajouté au stock.');
}

public function updateProduit(Request $request, $id)
{
    $produit = Produit::findOrFail($id);

    $data = $request->validate([
        'quantite_stock' => 'required|integer|min:0',
        'seuil_alerte' => 'required|integer|min:0',
    ]);

    $produit->update($data);

    AuditLog::create([
        'utilisateur_id' => auth()->id(),
        'action' => 'VALIDATION_MOUVEMENT_STOCK',
        'description' => "Mise à jour du stock de {$produit->nom_composant} (quantité: {$data['quantite_stock']}, seuil: {$data['seuil_alerte']}).",
        'adresse_ip' => $request->ip(),
    ]);

    return redirect()->back()->with('success', 'Stock mis à jour.');
}

public function revoquerToken(Request $request, $id)
{
    $utilisateur = Utilisateur::findOrFail($id);
    $utilisateur->tokens()->delete();

    AuditLog::create([
        'utilisateur_id' => auth()->id(),
        'action' => 'REVOCATION_TOKEN',
        'description' => "Révocation de tous les tokens mobiles de {$utilisateur->prenom} {$utilisateur->nom}.",
        'adresse_ip' => $request->ip(),
    ]);

    return redirect()->back()->with('success', 'Tokens révoqués pour cet utilisateur.');
}
public function storeUtilisateur(Request $request)
{
    $data = $request->validate([
        'nom' => 'required|string|max:255',
        'prenom' => 'required|string|max:255',
        'email' => 'required|email|unique:utilisateurs,email',
        'role' => 'required|in:EMPLOYE,TECHNICIEN,RESPONSABLE',
        'mot_de_passe' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()],
    ], [
        'mot_de_passe.confirmed' => 'La confirmation du mot de passe ne correspond pas.',
        'mot_de_passe.min' => 'Le mot de passe doit contenir au moins 8 caractères, une majuscule, une minuscule, un chiffre et un caractère spécial.',
    ]);

    Utilisateur::create([
        'nom' => $data['nom'],
        'prenom' => $data['prenom'],
        'email' => $data['email'],
        'role' => $data['role'],
        'mot_de_passe' => Hash::make($data['mot_de_passe']),
        'statut_compte' => true,
        'disponibilite' => $data['role'] === 'TECHNICIEN' ? true : false,
    ]);

    AuditLog::create([
        'utilisateur_id' => auth()->id(),
        'action' => 'CREATION_COMPTE',
        'description' => "Création du compte {$data['prenom']} {$data['nom']} ({$data['role']}).",
        'adresse_ip' => $request->ip(),
    ]);

    return redirect()->back()->with('success', 'Compte créé avec succès.');
}

public function toggleStatutCompte(Request $request, $id)
{
    $utilisateur = Utilisateur::findOrFail($id);

    if ($utilisateur->id === auth()->id()) {
        return redirect()->back()->withErrors(['compte' => 'Vous ne pouvez pas désactiver votre propre compte.']);
    }

    $utilisateur->update(['statut_compte' => !$utilisateur->statut_compte]);

    AuditLog::create([
        'utilisateur_id' => auth()->id(),
        'action' => 'CHANGEMENT_STATUT_COMPTE',
        'description' => "Compte de {$utilisateur->prenom} {$utilisateur->nom} " . ($utilisateur->statut_compte ? 'réactivé' : 'désactivé') . '.',
        'adresse_ip' => $request->ip(),
    ]);

    return redirect()->back()->with('success', 'Statut du compte mis à jour.');
}
public function storeMaintenancePreventive(Request $request)
{
    $data = $request->validate([
        'materiel_id' => 'required|exists:materiels,id',
        'titre' => 'required|string|max:255',
        'description' => 'required|string',
        'frequence_jour' => 'required|integer|min:1',
        'prochaine_reparation' => 'required|date',
    ]);

    $panne = Panne::create([
        'materiel_id' => $data['materiel_id'],
        'titre' => $data['titre'],
        'description' => $data['description'],
        'type_panne' => 'PREVENTIVE',
        'frequence_jour' => $data['frequence_jour'],
        'prochaine_reparation' => $data['prochaine_reparation'],
        'statut' => 'Planifiée',
        'date_declaration' => now(),
    ]);

    AuditLog::create([
        'utilisateur_id' => auth()->id(),
        'action' => 'CREATION_MAINTENANCE_PREVENTIVE',
        'description' => "Programmation d'une maintenance préventive « {$panne->titre} » sur {$panne->materiel->nom_equipement}, fréquence {$data['frequence_jour']} jours.",
        'adresse_ip' => $request->ip(),
    ]);

    return redirect()->back()->with('success', 'Maintenance préventive programmée.');
}
}
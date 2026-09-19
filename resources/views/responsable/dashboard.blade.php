@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Supervision & Administration (DSI / Responsable)</h2>
</div>

<!-- Navigation par Onglets -->
<ul class="nav nav-tabs nav-fill mb-4 border-bottom fw-bold" id="responsableTab" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active py-3" id="planification-tab" data-bs-toggle="tab" data-bs-target="#planification" type="button" role="tab" aria-controls="planification" aria-selected="true">
            📋 Planification & Affectation
            @if(!$pannesEnAttente->isEmpty())
                <span class="badge bg-danger ms-1">{{ $pannesEnAttente->count() }}</span>
            @endif
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link py-3" id="interventions-tab" data-bs-toggle="tab" data-bs-target="#interventions" type="button" role="tab" aria-controls="interventions" aria-selected="false">
            🛠️ Suivi des Interventions & Rapports
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link py-3" id="audit-tab" data-bs-toggle="tab" data-bs-target="#audit" type="button" role="tab" aria-controls="audit" aria-selected="false">
            🔒 Journal d'Audit (Sécurité)
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link py-3" id="materiel-tab" data-bs-toggle="tab" data-bs-target="#materiel" type="button" role="tab" aria-controls="materiel" aria-selected="false">
            🖥️ Parc Matériel
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link py-3" id="stock-tab" data-bs-toggle="tab" data-bs-target="#stock" type="button" role="tab" aria-controls="stock" aria-selected="false">
            📦 Stock & Accès
        </button>
    </li>
</ul>

<!-- Contenu des Onglets -->
<div class="tab-content" id="responsableTabContent">

    <!-- ONGLET 1 : Planification & Alertes Stock -->
    <div class="tab-pane fade show active" id="planification" role="tabpanel" aria-labelledby="planification-tab">
        <div class="row">
            <!-- Formulaire d'affectation -->
            <div class="col-md-7 mb-4">
                <div class="card shadow-sm border-primary">
                    <div class="card-header bg-primary text-white fw-bold">Planifier & Affecter un Technicien</div>
                    <div class="card-body">
                        @if($pannesEnAttente->isEmpty())
                            <div class="alert alert-success mb-0">
                                <strong>Tout est à jour !</strong> Aucune nouvelle panne en attente d'affectation.
                            </div>
                        @else
                            <form action="{{ route('responsable.planifier') }}" method="POST">
                                @csrf
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Référence Réparation</label>
                                    <input type="text" name="id" class="form-control" required value="REP-{{ date('Y') }}-00{{ rand(10,99) }}">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Sélectionner la panne signalée</label>
                                    <select name="panne_id" class="form-select" required>
                                        @foreach($pannesEnAttente as $p)
                                            <option value="{{ $p->id }}">
                                                {{ $p->titre }} — {{ $p->materiel->nom_equipement }} {{ $p->photo ? '📷 (Photo jointe)' : '' }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Technicien</label>
                                    <select name="technicien_id" class="form-select" required>
                                        @foreach($techniciensDispo as $t)
                                            <option value="{{ $t->id }}">
                                                {{ $t->prenom }} {{ $t->nom }} — {{ $t->disponibilite ? 'Disponible' : 'Occupé' }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Date et heure de début</label>
                                    <input type="datetime-local" name="date_debut" class="form-control" required>
                                </div>
                                <button type="submit" class="btn btn-primary w-100 fw-bold">Valider l'affectation</button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Alertes Stock Critique -->
            <div class="col-md-5 mb-4">
                <div class="card shadow-sm border-danger">
                    <div class="card-header bg-danger text-white fw-bold">Alertes Stock Critique (Produits)</div>
                    <div class="card-body">
                        @if($produitsCritiques->isEmpty())
                            <p class="text-success mb-0">Aucun produit sous le seuil d'alerte.</p>
                        @else
                            <ul class="list-group">
                                @foreach($produitsCritiques as $prod)
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    {{ $prod->nom_composant }}
                                    <span class="badge bg-danger">Stock: {{ $prod->quantite_stock }} / Seuil: {{ $prod->seuil_alerte }}</span>
                                </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Programmer une Maintenance Préventive -->
            <div class="col-12 mt-2">
                <div class="row">
                    <div class="col-md-5 mb-4">
                        <div class="card shadow-sm border-primary h-100">
                            <div class="card-header bg-primary text-white fw-bold">Programmer une Maintenance Préventive</div>
                            <div class="card-body">
                                <form action="{{ route('responsable.preventive.store') }}" method="POST">
                            @csrf
                            <div class="row g-2">
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">Matériel</label>
                                    <select name="materiel_id" class="form-select" required>
                                        <option value="">Choisir...</option>
                                        @foreach($materiels as $m)
                                            <option value="{{ $m->id }}">{{ $m->nom_equipement }} ({{ $m->numero_serie }})</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">Titre</label>
                                    <input type="text" name="titre" class="form-control" placeholder="Ex: Inspection trimestrielle" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">Description</label>
                                    <input type="text" name="description" class="form-control" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">Fréquence (jours)</label>
                                    <input type="number" name="frequence_jour" class="form-control" min="1" placeholder="Ex: 90" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">Première échéance</label>
                                    <input type="date" name="prochaine_reparation" class="form-control" required>
                                </div>
                                <div class="col-md-4 d-flex align-items-end">
                                    <button type="submit" class="btn btn-primary w-100 fw-bold">Programmer</button>
                                </div>
                            </div>
                                                        </form>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-7 mb-4">
                        <div class="card shadow-sm h-100">
                            <div class="card-header bg-secondary text-white fw-bold">Maintenances Préventives Programmées</div>
                            <div class="card-body p-0">
                                <table class="table table-sm table-striped mb-0 align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Titre</th>
                                            <th>Équipement</th>
                                            <th>Fréquence</th>
                                            <th>Prochaine échéance</th>
                                            <th>Statut</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($maintenancesPreventives as $mp)
                                        <tr>
                                            <td>{{ $mp->titre }}</td>
                                            <td>{{ $mp->materiel->nom_equipement ?? '—' }}</td>
                                            <td>{{ $mp->frequence_jour }} j</td>
                                            <td>{{ $mp->prochaine_reparation ? \Carbon\Carbon::parse($mp->prochaine_reparation)->format('d/m/Y') : '—' }}</td>
                                            <td>
                                                <span class="badge bg-{{ $mp->statut === 'Planifiée' ? 'secondary' : ($mp->statut === 'En attente' ? 'danger' : 'success') }}">
                                                    {{ $mp->statut }}
                                                </span>
                                            </td>
                                        </tr>
                                        @empty
                                        <tr><td colspan="5" class="text-center text-muted py-3">Aucune maintenance préventive programmée.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ONGLET 2 : Suivi des Interventions et Rapports -->
    <div class="tab-pane fade" id="interventions" role="tabpanel" aria-labelledby="interventions-tab">
        <div class="card shadow-sm">
            <div class="card-header bg-secondary text-white fw-bold">Suivi Général des Interventions & Rapports</div>
            <div class="card-body p-0">
                <table class="table table-striped table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Réf. Réparation</th>
                            <th>Équipement</th>
                            <th>Panne</th>
                            <th>Technicien</th>
                            <th>Statut</th>
                            <th>Rapport Technique</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($reparations as $rep)
                        <tr>
                            <td><strong>{{ $rep->id }}</strong></td>
                            <td>{{ $rep->panne->materiel->nom_equipement }}</td>
                            <td>
                                {{ $rep->panne->titre }}
                                @if($rep->panne->photo)
                                    <br><a href="{{ asset('storage/' . $rep->panne->photo) }}" target="_blank" class="badge bg-info text-decoration-none">Voir photo</a>
                                @endif
                            </td>
                            <td>
                                @foreach($rep->techniciens as $tech)
                                    <span class="badge bg-light text-dark border">{{ $tech->prenom }} {{ $tech->nom }}</span>
                                @endforeach
                            </td>
                            <td>
                                <span class="badge bg-{{ $rep->date_fin ? 'success' : 'warning text-dark' }}">
                                    {{ $rep->date_fin ? 'Clôturée' : 'En cours' }}
                                </span>
                            </td>
                            <td>{{ $rep->rapport_technique ?? 'Intervention en cours...' }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">Aucune intervention enregistrée.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ONGLET 3 : Journal d'Audit -->
    <div class="tab-pane fade" id="audit" role="tabpanel" aria-labelledby="audit-tab">
        <div class="card shadow-sm border-dark">
            <div class="card-header bg-dark text-white fw-bold">Journal d'Audit Récent</div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    @forelse($auditLogs as $log)
                    <li class="list-group-item py-2">
                        <small class="text-muted fw-bold">{{ \Carbon\Carbon::parse($log->created_at)->format('d/m/Y H:i') }}</small>
                        <span class="mx-2">|</span>
                        <span class="badge bg-secondary me-2">{{ $log->action }}</span>
                        <span>{{ $log->description }}</span>
                    </li>
                    @empty
                    <li class="list-group-item text-muted py-3 text-center">Aucune action enregistrée.</li>
                    @endforelse
                </ul>
            </div>
            @if(method_exists($auditLogs, 'links'))
            <div class="card-footer bg-white p-2">
                {{ $auditLogs->links() }}
            </div>
            @endif
        </div>
    </div>

    <!-- ONGLET 4 : Parc Matériel (CRUD) -->
    <div class="tab-pane fade" id="materiel" role="tabpanel" aria-labelledby="materiel-tab">
        <div class="row">
            <div class="col-md-5 mb-4">
                <div class="card shadow-sm border-primary">
                    <div class="card-header bg-primary text-white fw-bold">Ajouter un équipement</div>
                    <div class="card-body">
                        <form action="{{ route('responsable.materiel.store') }}" method="POST">
                            @csrf
                            <div class="mb-2">
                                <label class="form-label fw-semibold">Numéro de série</label>
                                <input type="text" name="numero_serie" class="form-control" required>
                            </div>
                            <div class="mb-2">
                                <label class="form-label fw-semibold">Nom de l'équipement</label>
                                <input type="text" name="nom_equipement" class="form-control" required>
                            </div>
                            <div class="mb-2">
                                <label class="form-label fw-semibold">Type</label>
                                <input type="text" name="type" class="form-control" required>
                            </div>
                            <div class="mb-2">
                                <label class="form-label fw-semibold">Date d'achat</label>
                                <input type="date" name="date_achat" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">État opérationnel</label>
                                <input type="text" name="etat_operationnel" class="form-control" value="En service" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100 fw-bold">Ajouter au parc</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-7 mb-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-secondary text-white fw-bold">Parc matériel existant</div>
                    <div class="card-body p-0">
                        <table class="table table-striped mb-0 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>N° série</th><th>Équipement</th><th>Type</th><th>État</th><th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($materiels as $m)
                                <tr>
                                    <td>{{ $m->numero_serie }}</td>
                                    <td>{{ $m->nom_equipement }}</td>
                                    <td>{{ $m->type }}</td>
                                    <td>
                                        <form action="{{ route('responsable.materiel.update', $m->id) }}" method="POST" class="d-flex gap-1">
                                            @csrf @method('PUT')
                                            <input type="hidden" name="numero_serie" value="{{ $m->numero_serie }}">
                                            <input type="hidden" name="nom_equipement" value="{{ $m->nom_equipement }}">
                                            <input type="hidden" name="type" value="{{ $m->type }}">
                                            <input type="hidden" name="date_achat" value="{{ $m->date_achat }}">
                                            <select name="etat_operationnel" class="form-select form-select-sm" onchange="this.form.submit()">
                                                @foreach(['En service','En panne','En maintenance','Hors service'] as $etat)
                                                    <option value="{{ $etat }}" {{ $m->etat_operationnel == $etat ? 'selected' : '' }}>{{ $etat }}</option>
                                                @endforeach
                                            </select>
                                        </form>
                                    </td>
                                    <td>
                                        <form action="{{ route('responsable.materiel.destroy', $m->id) }}" method="POST" onsubmit="return confirm('Supprimer ce matériel ?');">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">🗑️</button>
                                        </form>
                                    </td>
                                </tr>
                                @empty
                                <tr><td colspan="5" class="text-center text-muted py-3">Aucun matériel enregistré.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ONGLET 5 : Stock & Gestion des Accès -->
    <div class="tab-pane fade" id="stock" role="tabpanel" aria-labelledby="stock-tab">
        <div class="row">
            <div class="col-md-6 mb-4">
                <div class="card shadow-sm border-primary">
                    <div class="card-header bg-primary text-white fw-bold">Ajouter un produit / composant</div>
                    <div class="card-body">
                        <form action="{{ route('responsable.produit.store') }}" method="POST">
                            @csrf
                            <div class="mb-2">
                                <label class="form-label fw-semibold">Nom du composant</label>
                                <input type="text" name="nom_composant" class="form-control" required>
                            </div>
                            <div class="mb-2">
                                <label class="form-label fw-semibold">Type</label>
                                <input type="text" name="type_composant" class="form-control" required>
                            </div>
                            <div class="mb-2">
                                <label class="form-label fw-semibold">Quantité en stock</label>
                                <input type="number" name="quantite_stock" class="form-control" min="0" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Seuil d'alerte</label>
                                <input type="number" name="seuil_alerte" class="form-control" min="0" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100 fw-bold">Ajouter au stock</button>
                        </form>
                    </div>
                </div>

                <div class="card shadow-sm mt-3">
                    <div class="card-header bg-secondary text-white fw-bold">Modifier les seuils / quantités</div>
                    <div class="card-body p-0">
                        <table class="table table-sm mb-0">
                            <thead class="table-light"><tr><th>Produit</th><th>Stock</th><th>Seuil</th><th></th></tr></thead>
                            <tbody>
                                @foreach($materiels->isEmpty() ? [] : \App\Models\Produit::all() as $prod)
                                <tr>
                                    <form action="{{ route('responsable.produit.update', $prod->id_composant) }}" method="POST" class="d-flex w-100">
                                        @csrf @method('PUT')
                                        <td class="align-middle">{{ $prod->nom_composant }}</td>
                                        <td><input type="number" name="quantite_stock" value="{{ $prod->quantite_stock }}" class="form-control form-control-sm" min="0"></td>
                                        <td><input type="number" name="seuil_alerte" value="{{ $prod->seuil_alerte }}" class="form-control form-control-sm" min="0"></td>
                                        <td><button type="submit" class="btn btn-sm btn-outline-primary">💾</button></td>
                                    </form>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-md-6 mb-4">
                <div class="card shadow-sm border-dark mb-3">
                    <div class="card-header bg-dark text-white fw-bold">Créer un compte utilisateur</div>
                    <div class="card-body">
                        @if ($errors->has('compte'))
                            <div class="alert alert-danger py-2">{{ $errors->first('compte') }}</div>
                        @endif
                        <form action="{{ route('responsable.utilisateur.store') }}" method="POST">
                            @csrf
                            <div class="row g-2 mb-2">
                                <div class="col-6">
                                    <label class="form-label fw-semibold">Prénom</label>
                                    <input type="text" name="prenom" class="form-control" required>
                                </div>
                                <div class="col-6">
                                    <label class="form-label fw-semibold">Nom</label>
                                    <input type="text" name="nom" class="form-control" required>
                                </div>
                            </div>
                            <div class="mb-2">
                                <label class="form-label fw-semibold">Email</label>
                                <input type="email" name="email" class="form-control" required>
                            </div>
                            <div class="mb-2">
                                <label class="form-label fw-semibold">Rôle</label>
                                <select name="role" class="form-select" required>
                                    <option value="EMPLOYE">Employé</option>
                                    <option value="TECHNICIEN">Technicien</option>
                                    <option value="RESPONSABLE">Responsable</option>
                                </select>
                            </div>
                            <div class="mb-2">
                                <label class="form-label fw-semibold">Mot de passe</label>
                                <input type="password" name="mot_de_passe" class="form-control" required>
                                <div class="form-text">8 caractères min., majuscule, minuscule, chiffre, caractère spécial.</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Confirmation</label>
                                <input type="password" name="mot_de_passe_confirmation" class="form-control" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100 fw-bold">Créer le compte</button>
                        </form>
                    </div>
                </div>

                <div class="card shadow-sm">
                    <div class="card-header bg-secondary text-white fw-bold">Comptes existants — accès</div>
                    <div class="card-body p-0">
                        <table class="table table-sm mb-0 align-middle">
                            <thead class="table-light"><tr><th>Utilisateur</th><th>Rôle</th><th>Statut</th><th></th></tr></thead>
                            <tbody>
                                @foreach($utilisateurs as $u)
                                <tr>
                                    <td>{{ $u->prenom }} {{ $u->nom }}</td>
                                    <td><span class="badge bg-light text-dark border">{{ $u->role }}</span></td>
                                    <td>
                                        <form action="{{ route('responsable.utilisateur.statut', $u->id) }}" method="POST">
                                            @csrf @method('PUT')
                                            <button type="submit" class="btn btn-sm {{ $u->statut_compte ? 'btn-outline-success' : 'btn-outline-secondary' }}">
                                                {{ $u->statut_compte ? 'Actif' : 'Désactivé' }}
                                            </button>
                                        </form>
                                    </td>
                                    <td>
                                        <form action="{{ route('responsable.revoquerToken', $u->id) }}" method="POST" onsubmit="return confirm('Révoquer tous les tokens mobiles de cet utilisateur ?');">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Révoquer token</button>
                                        </form>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
@extends('layouts.app')

@section('content')
<style>
    /* Style personnalisé pour adoucir l'interface */
    .nav-tabs .nav-link {
        background-color: #e9ecef;
        color: #495057;
        border: none;
        border-radius: 8px 8px 0 0;
        margin-right: 4px;
        transition: all 0.2s ease-in-out;
    }
    .nav-tabs .nav-link:hover {
        background-color: #dee2e6;
    }
    .nav-tabs .nav-link.active {
        background-color: #ffffff !important;
        color: #0d6efd !important;
        border-top: 3px solid #0d6efd !important;
        box-shadow: 0 -2px 6px rgba(0,0,0,0.04);
    }
    .card {
        border: none;
        border-radius: 8px;
    }
</style>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="h3 fw-bold text-dark">Espace Technicien</h2>
    <form action="{{ route('technicien.disponibilite') }}" method="POST">
        @csrf
        <button type="submit" class="btn fw-bold {{ $technicien->disponibilite ? 'btn-success' : 'btn-secondary' }}">
            {{ $technicien->disponibilite ? '🟢 Disponible' : '⚪ Occupé' }} — cliquer pour changer
        </button>
    </form>
</div>

<!-- Navigation par Onglets -->
<ul class="nav nav-tabs nav-fill mb-4 border-bottom fw-bold" id="technicienTab" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active py-3" id="encours-tab" data-bs-toggle="tab" data-bs-target="#encours" type="button" role="tab" aria-controls="encours" aria-selected="true">
            🛠️ Interventions à traiter (En cours)
            @if(!$reparationsEnCours->isEmpty())
                <span class="badge bg-warning text-dark ms-2">{{ $reparationsEnCours->count() }}</span>
            @endif
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link py-3" id="historique-tab" data-bs-toggle="tab" data-bs-target="#historique" type="button" role="tab" aria-controls="historique" aria-selected="false">
            ✅ Historique des Réparations Clôturées
            @if(!$reparationsCloturees->isEmpty())
                <span class="badge bg-secondary ms-2">{{ $reparationsCloturees->count() }}</span>
            @endif
        </button>
    </li>
</ul>

<div class="tab-content" id="technicienTabContent">

    <!-- ONGLET 1 : Interventions En Cours -->
    <div class="tab-pane fade show active" id="encours" role="tabpanel" aria-labelledby="encours-tab">
        @if($reparationsEnCours->isEmpty())
            <div class="alert alert-success text-center py-4 fs-5 border-0 shadow-sm">
                <strong>Aucune intervention en cours.</strong> Vous n'avez aucun ticket à traiter pour le moment.
            </div>
        @else
            <div class="row">
                @foreach($reparationsEnCours as $rep)
                <div class="col-md-6 mb-4">
                    <div class="card shadow-sm border-warning h-100">
                        <div class="card-header bg-warning text-dark d-flex justify-content-between align-items-center fw-bold py-2">
                            <span>Ticket : {{ $rep->id }}</span>
                            <span class="badge bg-dark">En cours</span>
                        </div>
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title text-primary fw-bold">{{ $rep->panne->titre }}</h5>
                            <p class="card-text mb-1"><strong>Équipement :</strong> {{ $rep->panne->materiel->nom_equipement }}</p>
                            <p class="card-text mb-2"><strong>Description :</strong> {{ $rep->panne->description }}</p>

                            @if($rep->panne->photo)
                                <div class="mb-3">
                                    <a href="{{ asset('storage/' . $rep->panne->photo) }}" target="_blank" class="btn btn-sm btn-outline-info">📷 Voir la photo du problème</a>
                                </div>
                            @endif

                            <hr class="mt-auto">

                            <!-- Formulaire de clôture d'intervention -->
                            <form action="{{ route('technicien.cloturer', $rep->id) }}" method="POST">
                                @csrf
                                <div class="mb-3">
                                    <label class="form-label fw-bold small">Pièce(s) consommée(s) du stock</label>
                                    <div class="row g-2">
                                        <div class="col-8">
                                            <select name="produits[0][id]" class="form-select form-select-sm">
                                                <option value="">-- Aucune pièce --</option>
                                                @foreach($produits as $prod)
                                                    <option value="{{ $prod->id_composant }}">{{ $prod->nom_composant }} (Dispo: {{ $prod->quantite_stock }})</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-4">
                                            <input type="number" name="produits[0][quantite]" class="form-control form-control-sm" placeholder="Qté" min="1">
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold small">Rapport d'intervention technique</label>
                                    <textarea name="rapport_technique" class="form-control form-control-sm" rows="3" required placeholder="Saisissez le compte-rendu de votre réparation..."></textarea>
                                </div>

                                <button type="submit" class="btn btn-success w-100 fw-bold">
                                    ✅ Valider et clôturer la réparation
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        @endif
    </div>

    <!-- ONGLET 2 : Historique des Réparations Clôturées -->
    <div class="tab-pane fade" id="historique" role="tabpanel" aria-labelledby="historique-tab">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-dark text-white fw-bold py-3">
                Mes Interventions Terminées
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light border-bottom text-nowrap">
                            <tr>
                                <th style="width: 15%;" class="ps-3 py-3">Réf. Réparation</th>
                                <th style="width: 18%;" class="py-3">Équipement</th>
                                <th style="width: 22%;" class="py-3">Panne</th>
                                <th style="width: 15%;" class="py-3">Date & Heurede clôture</th>
                                <th style="width: 15%;" class="py-3">Pièces utilisées</th>
                                <th style="width: 15%;" class="py-3">Rapport technique</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($reparationsCloturees as $rep)
                            <tr>
                                <td class="ps-3"><strong>{{ $rep->id }}</strong></td>
                                <td>{{ $rep->panne->materiel->nom_equipement }}</td>
                                <td>{{ $rep->panne->titre }}</td>
                                <td>
                                    <span class="text-nowrap fw-semibold text-secondary">
                                        {{ \Carbon\Carbon::parse($rep->date_fin)->format('d/m/Y H:i') }}
                                    </span>
                                </td>
                                <td>
                                    @forelse($rep->produits as $prod)
                                        <span class="badge bg-info text-dark mb-1 d-inline-block">
                                            {{ $prod->nom_composant }} (x{{ $prod->pivot->quantite ?? 1 }})
                                        </span>
                                    @empty
                                        <span class="text-muted small">Aucune</span>
                                    @endforelse
                                </td>
                                <td><small class="text-wrap">{{ $rep->rapport_technique }}</small></td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">Vous n'avez pas encore de réparations clôturées à votre actif.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
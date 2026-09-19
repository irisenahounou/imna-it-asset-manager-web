@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="h3 fw-bold text-dark">Espace Employé</h2>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
        <strong>Succès !</strong> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<!-- Navigation par Onglets -->
<ul class="nav nav-tabs nav-fill mb-4 border-bottom-0 fw-bold" id="employeTab" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active py-3" id="signaler-tab" data-bs-toggle="tab" data-bs-target="#signaler" type="button" role="tab" aria-controls="signaler" aria-selected="true">
            ⚠️ Signaler une panne
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link py-3" id="mes-pannes-tab" data-bs-toggle="tab" data-bs-target="#mes-pannes" type="button" role="tab" aria-controls="mes-pannes" aria-selected="false">
            📋 Mes pannes déclarées (Suivi)
            @if(!$mesPannes->isEmpty())
                <span class="badge bg-primary ms-2">{{ $mesPannes->count() }}</span>
            @endif
        </button>
    </li>
</ul>

<div class="tab-content" id="employeTabContent">

    <!-- ONGLET 1 : Formulaire de signalement -->
    <div class="tab-pane fade show active" id="signaler" role="tabpanel" aria-labelledby="signaler-tab">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-danger text-white fw-bold py-3">
                        Formulaire de déclaration d'incident
                    </div>
                    <div class="card-body bg-white p-4">
                        <form action="{{ route('employe.signaler') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label fw-bold">Sélectionner l'équipement concerné</label>
                                <select name="materiel_id" class="form-select" required>
                                    <option value="">-- Choisir un matériel --</option>
                                    @foreach($materiels as $m)
                                        <option value="{{ $m->id }}">{{ $m->nom_equipement }} ({{ $m->numero_serie }})</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">Objet / Intitulé du problème</label>
                                <input type="text" name="titre" class="form-control" placeholder="Ex: Surchauffe, écran noir, blocage..." required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">Description détaillée de la panne</label>
                                <textarea name="description" class="form-control" rows="4" placeholder="Décrivez le dysfonctionnement constaté..." required></textarea>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-bold">Photo d'illustration (optionnel)</label>
                                <input type="file" name="photo" class="form-control" accept="image/*">
                            </div>

                            <button type="submit" class="btn btn-danger w-100 fw-bold py-2">
                                📤 Envoyer la déclaration de panne
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ONGLET 2 : Suivi des pannes déclarées -->
    <div class="tab-pane fade" id="mes-pannes" role="tabpanel" aria-labelledby="mes-pannes-tab">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-dark text-white fw-bold py-3">
                Historique et état d'avancement de mes tickets
            </div>
            <div class="card-body p-0 bg-white">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light border-bottom text-nowrap">
                            <tr>
                                <th style="width: 15%;" class="ps-3 py-3">Date</th>
                                <th style="width: 20%;" class="py-3">Équipement</th>
                                <th style="width: 25%;" class="py-3">Panne</th>
                                <th style="width: 15%;" class="py-3">Statut actuel</th>
                                <th style="width: 25%;" class="py-3">Photo / Info</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($mesPannes as $p)
                            <tr>
                                <td class="ps-3">
                                    <small class="fw-bold text-secondary">
                                        {{ \Carbon\Carbon::parse($p->created_at)->format('d/m/Y H:i') }}
                                    </small>
                                </td>
                                <td><strong>{{ $p->materiel->nom_equipement }}</strong></td>
                                <td>
                                    <strong>{{ $p->titre }}</strong>
                                    <br><small class="text-muted">{{ $p->description }}</small>
                                </td>
                                <td>
                                    @if($p->statut === 'En attente')
                                        <span class="badge bg-danger">Signalée (En attente)</span>
                                    @elseif($p->statut === 'En cours')
                                        <span class="badge bg-warning text-dark">En cours de traitement</span>
                                    @else
                                        <span class="badge bg-success">Résolue & Clôturée</span>
                                    @endif
                                </td>
                                <td>
                                    @if($p->photo)
                                        <a href="{{ asset('storage/' . $p->photo) }}" target="_blank" class="btn btn-sm btn-outline-info">📷 Voir photo</a>
                                    @else
                                        <span class="text-muted small">Aucune photo</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">Vous n'avez encore déclaré aucune panne.</td>
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
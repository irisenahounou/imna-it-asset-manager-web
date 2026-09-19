<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\EmployeController;
use App\Http\Controllers\TechnicienController;
use App\Http\Controllers\ResponsableController;
use App\Http\Controllers\NotificationController;

// Auth Routes
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
Route::get('/login/2fa', [AuthController::class, 'show2fa'])->name('2fa.show');
Route::post('/login/2fa', [AuthController::class, 'verify2fa'])->name('2fa.verify');

Route::get('/', function () {
    return redirect()->route('login');
});

// Notifications (tout utilisateur authentifié, quel que soit son rôle)
Route::middleware('auth')->group(function () {
    Route::post('/notifications/{id}/lue', [NotificationController::class, 'markAsRead'])->name('notifications.read');
});

// Espace Employé
Route::middleware(['auth', 'role:EMPLOYE'])->group(function () {
    Route::get('/employe/dashboard', [EmployeController::class, 'dashboard'])->name('employe.dashboard');
    Route::post('/employe/signaler', [EmployeController::class, 'signalerPanne'])->name('employe.signaler');
});

// Espace Technicien
Route::middleware(['auth', 'role:TECHNICIEN'])->group(function () {
    Route::get('/technicien/dashboard', [TechnicienController::class, 'dashboard'])->name('technicien.dashboard');
    Route::post('/technicien/disponibilite', [TechnicienController::class, 'toggleDisponibilite'])->name('technicien.disponibilite');
    Route::post('/technicien/cloturer/{id}', [TechnicienController::class, 'cloturerReparation'])->name('technicien.cloturer');
});

// Espace Responsable
Route::middleware(['auth', 'role:RESPONSABLE'])->group(function () {
    Route::get('/responsable/dashboard', [ResponsableController::class, 'dashboard'])->name('responsable.dashboard');
    Route::post('/responsable/planifier', [ResponsableController::class, 'planifierReparation'])->name('responsable.planifier');

    // Gestion des comptes utilisateurs
    Route::post('/responsable/utilisateur', [ResponsableController::class, 'storeUtilisateur'])->name('responsable.utilisateur.store');
    Route::put('/responsable/utilisateur/{id}/statut', [ResponsableController::class, 'toggleStatutCompte'])->name('responsable.utilisateur.statut');
    Route::post('/responsable/utilisateur/{id}/revoquer-token', [ResponsableController::class, 'revoquerToken'])->name('responsable.revoquerToken');

    // Gestion du Parc Matériel
    Route::post('/responsable/materiel', [ResponsableController::class, 'storeMateriel'])->name('responsable.materiel.store');
    Route::put('/responsable/materiel/{id}', [ResponsableController::class, 'updateMateriel'])->name('responsable.materiel.update');
    Route::delete('/responsable/materiel/{id}', [ResponsableController::class, 'destroyMateriel'])->name('responsable.materiel.destroy');

    // Gestion du Stock
    Route::post('/responsable/produit', [ResponsableController::class, 'storeProduit'])->name('responsable.produit.store');
    Route::put('/responsable/produit/{id}', [ResponsableController::class, 'updateProduit'])->name('responsable.produit.update');

    // Maintenance Préventive
    Route::post('/responsable/preventive', [ResponsableController::class, 'storeMaintenancePreventive'])->name('responsable.preventive.store');
});
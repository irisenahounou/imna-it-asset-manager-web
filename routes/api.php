<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthApiController;
use App\Http\Controllers\Api\EmployeApiController;
use App\Http\Controllers\Api\TechnicienApiController;

Route::post('/login', [AuthApiController::class, 'login']);
Route::post('/2fa/verify', [AuthApiController::class, 'verify2fa']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthApiController::class, 'logout']);
    Route::get('/me', [AuthApiController::class, 'me']);
});

Route::middleware(['auth:sanctum', 'role:EMPLOYE'])->group(function () {
    Route::get('/materiels', [EmployeApiController::class, 'materiels']);
    Route::post('/pannes', [EmployeApiController::class, 'store']);
    Route::get('/pannes/mine', [EmployeApiController::class, 'mine']);
});

Route::middleware(['auth:sanctum', 'role:TECHNICIEN'])->group(function () {
    Route::post('/disponibilite', [TechnicienApiController::class, 'toggleDisponibilite']);
    Route::get('/reparations/mine', [TechnicienApiController::class, 'mine']);
    Route::post('/reparations/{id}/cloturer', [TechnicienApiController::class, 'cloturer']);
    Route::get('/produits', [TechnicienApiController::class, 'produits']);
});
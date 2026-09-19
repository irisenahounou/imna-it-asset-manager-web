<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;

class Utilisateur extends Authenticatable
{
    use HasApiTokens,Notifiable;

    protected $table = 'utilisateurs';

    protected $fillable = [
        'nom', 'prenom', 'email', 'mot_de_passe', 
        'statut_compte', 'role', 'disponibilite', 'two_factor_secret','current_session_id'
    ];

    protected $hidden = ['mot_de_passe'];

    public function getAuthPassword()
    {
        return $this->mot_de_passe;
    }

    public function reparations()
    {
        return $this->belongsToMany(Reparation::class, 'reparation_technicien', 'technicien_id', 'reparation_id');
    }
}

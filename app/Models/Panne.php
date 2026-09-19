<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Panne extends Model
{
    protected $table = 'pannes';
    public $timestamps = false;

    protected $fillable = [
        'materiel_id',
        'user_id',
        'type_panne',
        'titre',
        'description',
        'photo',
        'statut',
        'date_declaration',
        'frequence_jour',
        'prochaine_reparation',
    ];
    public function user()
    {
     return $this->belongsTo(Utilisateur::class, 'user_id');
   }

    public function materiel()
    {
        return $this->belongsTo(Materiel::class, 'materiel_id');
    }

    public function reparation()
    {
        return $this->hasOne(Reparation::class, 'panne_id');
    }
    
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Materiel extends Model
{
    protected $table = 'materiels';
    public $timestamps = false;

    protected $fillable = ['numero_serie', 'nom_equipement', 'type', 'date_achat', 'etat_operationnel'];

    public function pannes()
    {
        return $this->hasMany(Panne::class, 'materiel_id');
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reparation extends Model
{
    protected $table = 'reparations';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = ['id', 'date_debut', 'date_fin', 'rapport_technique', 'panne_id'];

    public function panne()
    {
        return $this->belongsTo(Panne::class, 'panne_id');
    }

    public function techniciens()
    {
        return $this->belongsToMany(Utilisateur::class, 'reparation_technicien', 'reparation_id', 'technicien_id');
    }

    public function produits()
    {
        return $this->belongsToMany(Produit::class, 'details_reparation', 'reparation_id', 'produit_id')
                    ->withPivot('id', 'quantite', 'commentaire');
    }
}

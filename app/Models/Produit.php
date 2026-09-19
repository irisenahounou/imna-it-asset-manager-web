<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Produit extends Model
{
    protected $table = 'produits';
    protected $primaryKey = 'id_composant';
    public $timestamps = false;

    protected $fillable = ['nom_composant', 'type_composant', 'quantite_stock', 'seuil_alerte'];

    public function verifierSeuilCritique(): bool
    {
        return $this->quantite_stock <= $this->seuil_alerte;
    }

    public function reparations()
    {
        return $this->belongsToMany(Reparation::class, 'details_reparation', 'produit_id', 'reparation_id')
                    ->withPivot('id', 'quantite', 'commentaire');
    }
}

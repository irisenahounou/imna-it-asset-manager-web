<?php

namespace App\Notifications;

use App\Models\Produit;
use Illuminate\Notifications\Notification;

class StockCritiqueNotification extends Notification
{
    public function __construct(protected Produit $produit) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'titre'   => 'Seuil de stock critique atteint',
            'message' => "{$this->produit->nom_composant} : {$this->produit->quantite_stock} restant(s) (seuil {$this->produit->seuil_alerte}).",
            'produit_id' => $this->produit->id_composant,
        ];
    }
}
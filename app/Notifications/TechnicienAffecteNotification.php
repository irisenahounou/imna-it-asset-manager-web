<?php

namespace App\Notifications;

use App\Models\Reparation;
use Illuminate\Notifications\Notification;

class TechnicienAffecteNotification extends Notification
{
    public function __construct(protected Reparation $reparation) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'titre'   => 'Nouvelle intervention affectée',
            'message' => "Réparation {$this->reparation->id} — {$this->reparation->panne->titre}",
            'reparation_id' => $this->reparation->id,
        ];
    }
}
<?php

namespace App\Notifications;

use App\Models\Panne;
use Illuminate\Notifications\Notification;

class PanneDeclareeNotification extends Notification
{
    public function __construct(protected Panne $panne) {}

    public function via(object $notifiable): array
    {
        // Ajouter 'mail' ici le jour où le SMTP sera configuré en production.
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'titre'   => 'Nouvelle panne déclarée',
            'message' => "{$this->panne->titre} — {$this->panne->materiel->nom_equipement}",
            'panne_id' => $this->panne->id,
        ];
    }
}
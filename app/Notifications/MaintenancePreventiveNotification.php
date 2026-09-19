<?php

namespace App\Notifications;

use App\Models\Panne;
use Illuminate\Notifications\Notification;

class MaintenancePreventiveNotification extends Notification
{
    public function __construct(protected Panne $panne) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'titre'   => 'Maintenance préventive à programmer',
            'message' => "{$this->panne->titre} — {$this->panne->materiel->nom_equipement} arrive à échéance.",
            'panne_id' => $this->panne->id,
        ];
    }
}
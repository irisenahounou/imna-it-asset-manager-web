<?php

namespace App\Notifications;

use App\Models\Reparation;
use Illuminate\Notifications\Notification;

class ReparationClotureeNotification extends Notification
{
    public function __construct(protected Reparation $reparation) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'titre'   => 'Votre panne a été réparée',
            'message' => "L'intervention sur « {$this->reparation->panne->titre} » est terminée.",
            'reparation_id' => $this->reparation->id,
        ];
    }
}
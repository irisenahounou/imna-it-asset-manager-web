<?php

namespace App\Console\Commands;

use App\Models\Panne;
use App\Models\Utilisateur;
use App\Models\AuditLog;
use App\Notifications\MaintenancePreventiveNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;
use Carbon\Carbon;

class GenererAlertesPreventives extends Command
{
    protected $signature = 'imna:alertes-preventives';
    protected $description = "Génère les alertes de maintenance préventive à échéance (Système SGR) et replanifie automatiquement la prochaine échéance.";

    public function handle(): int
    {
        $pannesEcheance = Panne::where('type_panne', 'PREVENTIVE')
            ->whereDate('prochaine_reparation', '<=', Carbon::today())
            ->with('materiel')
            ->get();

        if ($pannesEcheance->isEmpty()) {
            $this->info("Aucune maintenance préventive à échéance aujourd'hui.");
            return self::SUCCESS;
        }

        $responsables = Utilisateur::where('role', 'RESPONSABLE')->get();

        foreach ($pannesEcheance as $panne) {
            // generer_alerte_automatique()
            Notification::send($responsables, new MaintenancePreventiveNotification($panne));

            // generer_alerte_automatique() : la maintenance devient assignable, comme une panne corrective
            $panne->update(['statut' => 'En attente']);

            // planifier_prochaine_maintenance() : échéance suivante déjà calculée pour le prochain cycle
            if ($panne->frequence_jour) {
                $panne->update([
                    'prochaine_reparation' => Carbon::today()->addDays($panne->frequence_jour),
                ]);
            }

            AuditLog::create([
                'utilisateur_id' => null,
                'action' => 'ALERTE_PREVENTIVE_AUTO',
                'description' => "Alerte de maintenance préventive générée pour « {$panne->titre} » ({$panne->materiel->nom_equipement}), prochaine échéance replanifiée.",
                'adresse_ip' => null,
            ]);

            $this->info("Alerte envoyée : {$panne->titre} ({$panne->materiel->nom_equipement}).");
        }

        return self::SUCCESS;
    }
}
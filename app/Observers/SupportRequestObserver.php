<?php

namespace App\Observers;

use App\Enums\ProgramRole;
use App\Models\ProgramMember;
use App\Models\SupportRequest;
use App\Notifications\SupportRequestReceivedNotification;

/**
 * Avisa al staff cuando el paciente o el cuidador reportan una solicitud nueva — antes
 * nadie se enteraba sin entrar manualmente a revisar la lista, así que una dificultad
 * urgente podía quedar sin respuesta. Se notifica al auditor asignado al caso; si el
 * caso todavía no tiene auditor asignado, se notifica a quienes lideran el programa.
 */
class SupportRequestObserver
{
    public function created(SupportRequest $request): void
    {
        $request->loadMissing('case.assignedAuditor');
        $case = $request->case;

        if (! $case) {
            return;
        }

        $notification = new SupportRequestReceivedNotification($request);

        if ($case->assignedAuditor) {
            $case->assignedAuditor->notify($notification);

            return;
        }

        ProgramMember::query()
            ->where('clinical_program_id', $case->clinical_program_id)
            ->where('is_active', true)
            ->whereIn('role', [ProgramRole::Leader, ProgramRole::ClinicalLeader, ProgramRole::Coordinator])
            ->with('user')
            ->get()
            ->pluck('user')
            ->filter(fn ($user) => $user?->is_active)
            ->each(fn ($user) => $user->notify($notification));
    }
}

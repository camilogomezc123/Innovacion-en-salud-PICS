<?php

namespace App\Livewire\Portal;

use App\Http\Controllers\Portal\PortalHomeController;
use App\Models\Caregiver;
use App\Models\Patient;
use App\Support\Posuci\CaseAccess;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class EducationComponent extends Component
{
    private function actor(): Patient|Caregiver|null
    {
        $case = PortalHomeController::currentCase();
        if (! $case) {
            return null;
        }

        $patient = Auth::guard('patient')->user();
        $caregiver = Auth::guard('caregiver')->user();

        if ($patient instanceof Patient && CaseAccess::patientCanAccess($patient, $case)) {
            return $patient;
        }

        if ($caregiver instanceof Caregiver && CaseAccess::caregiverCanAccess($caregiver, $case)) {
            return $caregiver;
        }

        return null;
    }

    public function markViewed(int $assignmentId): void
    {
        $case = PortalHomeController::currentCase();
        $actor = $this->actor();
        abort_unless($case && $actor, 403);

        $assignment = $case->educationAssignments()->findOrFail($assignmentId);
        $assignment->update([
            'viewed_by_type' => $actor::class,
            'viewed_by_id' => $actor->id,
            'viewed_at' => now(),
        ]);
    }

    public function render()
    {
        $case = PortalHomeController::currentCase();

        $assignments = $case
            ? $case->educationAssignments()
                ->with('resource')
                ->whereHas('resource', fn ($query) => $query->where('is_active', true))
                ->get()
            : collect();

        return view('livewire.portal.education-component', [
            'assignments' => $assignments,
        ]);
    }
}

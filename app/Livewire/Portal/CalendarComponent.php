<?php

namespace App\Livewire\Portal;

use App\Http\Controllers\Portal\PortalHomeController;
use App\Models\Caregiver;
use App\Models\Patient;
use App\Models\PersonalReminder;
use App\Support\Posuci\CaseAccess;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Validate;
use Livewire\Component;

class CalendarComponent extends Component
{
    #[Validate('required|string|min:2')]
    public string $title = '';

    #[Validate('required|date')]
    public string $remind_at = '';

    #[Validate('nullable|string')]
    public string $notes = '';

    public function mount(): void
    {
        $this->remind_at = now()->addHour()->format('Y-m-d\TH:i');
    }

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

    public function addReminder(): void
    {
        $case = PortalHomeController::currentCase();
        $actor = $this->actor();
        abort_unless($case && $actor, 403);

        $this->validate();

        PersonalReminder::query()->create([
            'pics_case_id' => $case->id,
            'title' => $this->title,
            'remind_at' => $this->remind_at,
            'notes' => $this->notes ?: null,
            'created_by_type' => $actor::class,
            'created_by_id' => $actor->id,
        ]);

        $this->reset(['title', 'notes']);
        $this->remind_at = now()->addHour()->format('Y-m-d\TH:i');

        session()->flash('calendar_status', 'Recordatorio agregado.');
        $this->dispatch('celebrate');
        $this->dispatch('calendar-refresh');
    }

    public function render()
    {
        return view('livewire.portal.calendar-component', [
            'case' => PortalHomeController::currentCase(),
        ]);
    }
}

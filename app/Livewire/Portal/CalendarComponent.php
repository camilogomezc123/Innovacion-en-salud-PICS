<?php

namespace App\Livewire\Portal;

use App\Http\Controllers\Portal\PortalHomeController;
use App\Models\Caregiver;
use App\Models\Patient;
use App\Models\PersonalReminder;
use App\Models\PicsAgendaItem;
use App\Notifications\AppointmentResponseNotification;
use App\Support\Posuci\CaseAccess;
use App\Support\Posuci\StaffNotifier;
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

    public function respondToAppointment(int $itemId, string $response): void
    {
        $case = PortalHomeController::currentCase();
        $actor = $this->actor();
        abort_unless($case && $actor, 403);
        abort_unless(array_key_exists($response, PicsAgendaItem::RESPONSES), 422);

        $item = PicsAgendaItem::query()
            ->where('pics_case_id', $case->id)
            ->whereIn('type', PicsAgendaItem::RESPONDABLE_TYPES)
            ->findOrFail($itemId);

        $item->update([
            'patient_response' => $response,
            'patient_response_at' => now(),
            'patient_responded_by_type' => $actor::class,
            'patient_responded_by_id' => $actor->id,
        ]);

        StaffNotifier::notifyCaseStaff($case, new AppointmentResponseNotification($item));

        if ($response === 'confirmada') {
            $this->dispatch('celebrate');
        }

        $this->dispatch('calendar-refresh');
        session()->flash('calendar_status', $response === 'confirmada' ? '¡Confirmaste tu asistencia!' : 'Le avisamos a tu equipo que no podrás asistir.');
    }

    public function render()
    {
        return view('livewire.portal.calendar-component', [
            'case' => PortalHomeController::currentCase(),
        ]);
    }
}

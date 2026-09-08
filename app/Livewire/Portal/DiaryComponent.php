<?php

namespace App\Livewire\Portal;

use App\Http\Controllers\Portal\PortalHomeController;
use App\Models\Caregiver;
use App\Models\DiaryEntry;
use App\Models\Patient;
use App\Support\Posuci\CaseAccess;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Validate;
use Livewire\Component;

class DiaryComponent extends Component
{
    #[Validate('required|date')]
    public string $entry_date = '';

    #[Validate('required|string|min:5')]
    public string $content = '';

    #[Validate('nullable|string')]
    public string $message_to_patient = '';

    #[Validate('nullable|string')]
    public string $meaningful_memory = '';

    public bool $visible_to_patient = true;

    public function mount(): void
    {
        $this->entry_date = now()->toDateString();
    }

    public function save(): void
    {
        $case = PortalHomeController::currentCase();
        $caregiver = Auth::guard('caregiver')->user();

        abort_unless($case && $caregiver instanceof Caregiver && CaseAccess::caregiverCanWriteDiary($caregiver, $case), 403);

        $this->validate();

        DiaryEntry::query()->create([
            'pics_case_id' => $case->id,
            'authorable_type' => Caregiver::class,
            'authorable_id' => $caregiver->id,
            'entry_date' => $this->entry_date,
            'content' => $this->content,
            'message_to_patient' => $this->message_to_patient ?: null,
            'meaningful_memory' => $this->meaningful_memory ?: null,
            'is_draft' => false,
            'visible_to_patient' => $this->visible_to_patient,
        ]);

        $this->reset(['content', 'message_to_patient', 'meaningful_memory']);
        $this->entry_date = now()->toDateString();

        session()->flash('diary_status', 'Entrada guardada.');
    }

    public function render()
    {
        $case = PortalHomeController::currentCase();
        $patient = Auth::guard('patient')->user();
        $caregiver = Auth::guard('caregiver')->user();

        $canWrite = $case && $caregiver instanceof Caregiver && CaseAccess::caregiverCanWriteDiary($caregiver, $case);

        $entries = collect();

        if ($case) {
            $query = $case->diaryEntries()->where('is_draft', false)->orderByDesc('entry_date');

            if ($patient instanceof Patient) {
                $query->where('visible_to_patient', true);
            }

            $entries = $query->with('authorable')->get();
        }

        return view('livewire.portal.diary-component', [
            'case' => $case,
            'entries' => $entries,
            'canWrite' => $canWrite,
            'isPatient' => $patient instanceof Patient,
        ]);
    }
}

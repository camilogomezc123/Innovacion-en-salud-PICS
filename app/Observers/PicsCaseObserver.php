<?php

namespace App\Observers;

use App\Enums\CaseStatus;
use App\Models\PicsCase;
use App\Services\ClinicalAuditService;
use App\Support\HubMetrics;
use App\Support\ProgramAccess;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

class PicsCaseObserver
{
    public function creating(PicsCase $case): void
    {
        $case->clinical_program_id ??= ProgramAccess::program('pics')?->id;

        if (! $case->case_sequence) {
            $next = ((int) PicsCase::query()->lockForUpdate()->max('case_sequence')) + 1;
            $case->case_sequence = $next;
            $case->case_number = 'PICS-'.$next;
        }

        $case->created_by ??= auth('web')->id();
        $case->updated_by ??= auth('web')->id();

        $this->syncWorkflowDates($case);
    }

    public function updating(PicsCase $case): void
    {
        $this->authorizeStatusTransition($case);
        $case->updated_by = auth('web')->id();

        if (! $case->isDirty('status')
            && $case->status === CaseStatus::Assigned
            && auth('web')->id() === $case->assigned_auditor_id) {
            $case->status = CaseStatus::InReview;
        }

        $this->syncWorkflowDates($case);
    }

    public function created(PicsCase $case): void
    {
        HubMetrics::forget();
        $this->record($case, 'created');
    }

    public function updated(PicsCase $case): void
    {
        HubMetrics::forget();

        foreach ($case->getChanges() as $field => $newValue) {
            if (in_array($field, ['updated_at', 'updated_by'], true)) {
                continue;
            }

            $this->record($case, 'updated', $field, $case->getOriginal($field), $newValue);
        }
    }

    public function deleted(PicsCase $case): void
    {
        HubMetrics::forget();
    }

    private function syncWorkflowDates(PicsCase $case): void
    {
        if (blank($case->month)) {
            $periodDate = $case->enrollment_at ?? now();
            $case->month = CarbonImmutable::parse($periodDate)->format('Y/m');
        }

        if ($case->isDirty('assigned_auditor_id') && $case->assigned_auditor_id) {
            $case->assigned_at = now();
        }

        if ($case->status === null) {
            return;
        }

        $status = $case->status instanceof CaseStatus ? $case->status : CaseStatus::from($case->status);

        if ($status === CaseStatus::InReview && ! $case->analysis_started_at) {
            $case->analysis_started_at = now();
        }

        if ($status === CaseStatus::Pending && ! $case->auditor_finalized_at) {
            $case->auditor_finalized_at = now();
        }

        if ($status === CaseStatus::Completed && ! $case->completed_at) {
            $case->completed_at = now();
        }

        if ($status === CaseStatus::Cancelled) {
            $case->is_cancelled = true;
            $case->cancelled_at ??= now();
        }
    }

    private function authorizeStatusTransition(PicsCase $case): void
    {
        if (! $case->isDirty('status') || ! auth('web')->check()) {
            return;
        }

        $previous = $case->getOriginal('status');
        $next = $case->status instanceof CaseStatus ? $case->status->value : $case->status;
        $user = auth('web')->user();
        $isManager = $user->canManagePicsCases();
        $isAssignedAuditor = $case->assigned_auditor_id === $user->id;

        if ($next === CaseStatus::Cancelled->value && ! $isManager) {
            throw ValidationException::withMessages(['status' => 'Solo el líder o el administrador pueden anular un caso.']);
        }

        if ($previous === CaseStatus::Completed->value && $next !== CaseStatus::Completed->value && ! $isManager) {
            throw ValidationException::withMessages(['status' => 'Solo el líder o el administrador pueden reabrir un caso finalizado.']);
        }

        if ($next === CaseStatus::Completed->value && ! $isManager) {
            throw ValidationException::withMessages(['status' => 'Solo el líder o el administrador finalizan la revisión.']);
        }

        if ($next === CaseStatus::Pending->value && ! ($isManager || $isAssignedAuditor)) {
            throw ValidationException::withMessages(['status' => 'Solo el auditor asignado puede finalizar el seguimiento de este caso.']);
        }
    }

    private function record(
        PicsCase $case,
        string $event,
        ?string $field = null,
        mixed $oldValue = null,
        mixed $newValue = null,
    ): void {
        if (! $case->clinical_program_id) {
            return;
        }

        app(ClinicalAuditService::class)->record(
            $case,
            $case->clinical_program_id,
            $event,
            $field,
            $oldValue,
            $newValue,
        );
    }
}

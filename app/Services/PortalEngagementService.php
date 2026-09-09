<?php

namespace App\Services;

use App\Models\Caregiver;
use App\Models\Patient;
use App\Models\PicsCase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Trazabilidad del uso real del portal (/portal) por parte del paciente y la familia
 * — cuántas veces entran, si escriben el diario, si reportan avances, si diligencian
 * "Cómo me siento", si piden ayuda y qué tan rápido se les responde. Es un eje de
 * medición distinto al de PicsIndicatorService (que mide los instrumentos clínicos
 * PICS, no el comportamiento de uso de la plataforma).
 */
class PortalEngagementService
{
    /**
     * Instantánea de uso del portal para un caso puntual. Asume que las relaciones ya
     * vienen precargadas (patient, caregiverAuthorizations.caregiver, diaryEntries,
     * recoveryGoals.progressReports, followups, supportRequests, recoveryPassport) —
     * no dispara consultas adicionales por caso.
     *
     * @return array<string, mixed>
     */
    public function caseSnapshot(PicsCase $case): array
    {
        $patient = $case->patient;
        $authorization = $case->caregiverAuthorizations->whereNull('revoked_at')->first();
        $caregiver = $authorization?->caregiver;

        $diaryCount = $case->diaryEntries->count();

        $goalReports = $case->recoveryGoals->flatMap->progressReports;
        $goalReportsByPatient = $goalReports->where('reporter_type', Patient::class)->count();
        $goalReportsByCaregiver = $goalReports->where('reporter_type', Caregiver::class)->count();

        $selfReportedFollowups = $case->followups->whereNotNull('submitted_by_type');
        $confirmedFollowups = $selfReportedFollowups->whereNotNull('confirmed_at');

        $requests = $case->supportRequests;
        $answeredRequests = $requests->whereNotNull('responded_at');
        $avgResponseHours = $answeredRequests->isEmpty() ? null : round(
            $answeredRequests->avg(fn ($r) => $r->created_at->diffInMinutes($r->responded_at) / 60),
            1,
        );

        $passport = $case->recoveryPassport;
        $passportStatus = match (true) {
            $passport === null || $passport->reported_at === null => 'sin_diligenciar',
            $passport->is_confirmed => 'confirmado',
            default => 'reportado',
        };

        $lastActivity = collect([
            $patient?->last_login_at,
            $caregiver?->last_login_at,
            $case->diaryEntries->max('created_at'),
            $goalReports->max('reported_at'),
            $selfReportedFollowups->max('followed_up_at'),
            $requests->max('created_at'),
            $passport?->reported_at,
        ])->filter()->map(fn ($d) => Carbon::parse($d))->sort()->last();

        return [
            'caregiver_authorized' => $authorization !== null,
            'caregiver_authorized_at' => $authorization?->authorized_at,
            'caregiver_last_login_at' => $caregiver?->last_login_at,
            'patient_has_account' => filled($patient?->email),
            'patient_last_login_at' => $patient?->last_login_at,
            'diary_entries_count' => $diaryCount,
            'goal_reports_total' => $goalReports->count(),
            'goal_reports_by_patient' => $goalReportsByPatient,
            'goal_reports_by_caregiver' => $goalReportsByCaregiver,
            'wellbeing_self_reports_count' => $selfReportedFollowups->count(),
            'wellbeing_confirmed_count' => $confirmedFollowups->count(),
            'support_requests_total' => $requests->count(),
            'support_requests_answered' => $answeredRequests->count(),
            'support_requests_avg_response_hours' => $avgResponseHours,
            'passport_status' => $passportStatus,
            'last_portal_activity_at' => $lastActivity,
        ];
    }

    /**
     * Resumen institucional a partir de una colección de casos (ya con las mismas
     * relaciones precargadas que espera caseSnapshot()).
     *
     * @param  Collection<int, PicsCase>  $cases
     * @return array<string, mixed>
     */
    public function aggregate(Collection $cases): array
    {
        if ($cases->isEmpty()) {
            return [
                'total_cases' => 0, 'caregiver_authorized_pct' => null, 'any_login_pct' => null,
                'diary_activity_pct' => null, 'wellbeing_self_report_pct' => null,
                'avg_goal_reports_per_case' => null, 'patient_report_share_pct' => null,
                'avg_support_response_hours' => null, 'passport_confirmed_pct' => null,
            ];
        }

        $snapshots = $cases->map(fn (PicsCase $case) => $this->caseSnapshot($case));

        $withLogin = $snapshots->filter(fn (array $s) => $s['patient_last_login_at'] || $s['caregiver_last_login_at']);
        $withDiary = $snapshots->filter(fn (array $s) => $s['diary_entries_count'] > 0);
        $withWellbeing = $snapshots->filter(fn (array $s) => $s['wellbeing_self_reports_count'] > 0);
        $totalGoalReports = $snapshots->sum('goal_reports_total');
        $patientGoalReports = $snapshots->sum('goal_reports_by_patient');
        $responseTimes = $snapshots->pluck('support_requests_avg_response_hours')->filter();
        $confirmedPassports = $snapshots->filter(fn (array $s) => $s['passport_status'] === 'confirmado');

        return [
            'total_cases' => $cases->count(),
            'caregiver_authorized_pct' => $this->percentage($snapshots->where('caregiver_authorized', true)->count(), $cases->count()),
            'any_login_pct' => $this->percentage($withLogin->count(), $cases->count()),
            'diary_activity_pct' => $this->percentage($withDiary->count(), $cases->count()),
            'wellbeing_self_report_pct' => $this->percentage($withWellbeing->count(), $cases->count()),
            'avg_goal_reports_per_case' => round($totalGoalReports / $cases->count(), 1),
            'patient_report_share_pct' => $this->percentage($patientGoalReports, $totalGoalReports),
            'avg_support_response_hours' => $responseTimes->isEmpty() ? null : round($responseTimes->avg(), 1),
            'passport_confirmed_pct' => $this->percentage($confirmedPassports->count(), $cases->count()),
        ];
    }

    private function percentage(int $numerator, int $denominator): ?float
    {
        return $denominator === 0 ? null : round(($numerator / $denominator) * 100, 1);
    }
}

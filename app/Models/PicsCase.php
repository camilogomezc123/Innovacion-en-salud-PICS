<?php

namespace App\Models;

use App\Enums\CaseStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

#[Fillable([
    'clinical_program_id', 'site_id', 'patient_id', 'icu_stay_id', 'assigned_auditor_id', 'created_by', 'updated_by',
    'case_number', 'case_sequence', 'status', 'month', 'enrollment_at', 'enrollment_source',
    'mechanical_ventilation_days', 'delirium_days', 'icu_los_days', 'sedation_deep_days',
    'clinical_data', 'field_status', 'is_valid', 'is_cancelled', 'cancellation_reason',
    'assigned_at', 'analysis_started_at', 'auditor_finalized_at', 'completed_at', 'cancelled_at',
])]
class PicsCase extends Model
{
    public const ENROLLMENT_SOURCES = [
        'icu_discharge' => 'Egreso de UCI',
        'hospital_discharge' => 'Egreso hospitalario',
        'referral' => 'Remisión externa',
        'other' => 'Otro',
    ];

    /**
     * Campos clave usados para medir completitud del registro. Un campo vacío no
     * implica incumplimiento clínico — ver field_status para "no realizado/no aplica/desconocido".
     */
    public const KEY_TRACKING_FIELDS = [
        'enrollment_at' => 'Ingreso al programa',
        'icu_los_days' => 'Estancia UCI de origen',
    ];

    public const FIELD_STATUS_OPTIONS = [
        'not_performed' => 'No realizado',
        'not_applicable' => 'No aplica',
        'unknown' => 'Desconocido',
    ];

    protected function casts(): array
    {
        return [
            'status' => CaseStatus::class,
            'enrollment_at' => 'datetime',
            'mechanical_ventilation_days' => 'decimal:3',
            'delirium_days' => 'decimal:3',
            'icu_los_days' => 'decimal:3',
            'sedation_deep_days' => 'decimal:3',
            'clinical_data' => 'array',
            'field_status' => 'array',
            'is_valid' => 'boolean',
            'is_cancelled' => 'boolean',
            'assigned_at' => 'datetime',
            'analysis_started_at' => 'datetime',
            'auditor_finalized_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function icuStay(): BelongsTo
    {
        return $this->belongsTo(IcuStay::class);
    }

    public function assignedAuditor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_auditor_id');
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(ClinicalProgram::class, 'clinical_program_id');
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function activityLogs(): MorphMany
    {
        return $this->morphMany(ClinicalAudit::class, 'auditable');
    }

    public function followups(): HasMany
    {
        return $this->hasMany(PicsFollowup::class);
    }

    public function referrals(): HasMany
    {
        return $this->hasMany(PicsReferral::class);
    }

    public function diaryEntries(): HasMany
    {
        return $this->hasMany(DiaryEntry::class);
    }

    public function recoveryGoals(): HasMany
    {
        return $this->hasMany(RecoveryGoal::class);
    }

    public function caregiverAuthorizations(): HasMany
    {
        return $this->hasMany(CaregiverAuthorization::class);
    }

    /**
     * Completitud de los campos clave. Los campos marcados "no aplica" se excluyen del
     * denominador; no usa ni afecta los indicadores institucionales — es calidad del dato.
     *
     * @return array{percentage: float, items: array<int, array{field: string, label: string, status: string}>}
     */
    public function completenessSummary(): array
    {
        $overrides = $this->field_status ?? [];

        $items = collect(self::KEY_TRACKING_FIELDS)->map(function (string $label, string $field) use ($overrides): array {
            if (filled($this->{$field})) {
                return ['field' => $field, 'label' => $label, 'status' => 'filled'];
            }

            $override = $overrides[$field] ?? null;

            return ['field' => $field, 'label' => $label, 'status' => $override ?? 'pending'];
        })->values();

        $applicable = $items->reject(fn (array $item): bool => $item['status'] === 'not_applicable');
        $filled = $applicable->where('status', 'filled');

        $percentage = $applicable->isEmpty() ? 100.0 : round(($filled->count() / $applicable->count()) * 100, 1);

        return ['percentage' => $percentage, 'items' => $items->all()];
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'pics_case_id', 'checkpoint', 'contact_method', 'contact_achieved', 'functional_capacity', 'mobility',
    'strength', 'fatigue', 'pain', 'sleep_quality',
    'cognition_tool', 'cognition_result', 'cognition_positive',
    'anxiety_tool', 'anxiety_result', 'anxiety_positive',
    'depression_tool', 'depression_result', 'depression_positive',
    'ptsd_tool', 'ptsd_result', 'ptsd_positive',
    'medications_review', 'readmission', 'return_to_work', 'quality_of_life_tool', 'quality_of_life_result',
    'caregiver_burden_tool', 'caregiver_burden_result', 'referred_to', 'notes', 'followed_up_at', 'responsible_user_id',
])]
class PicsFollowup extends Model
{
    public const CHECKPOINTS = [
        '48_72h' => '48-72 horas post-egreso',
        '7d' => '7 días',
        '30d' => '30 días',
        '3m' => '3 meses',
        '6m' => '6 meses',
        '12m' => '12 meses',
    ];

    /**
     * Los campos *_positive los marca explícitamente quien registra el seguimiento
     * (interpretación clínica del instrumento usado, cada uno con su propio punto de
     * corte). El indicador PICS-01 solo lee estas banderas — nunca infiere "positivo"
     * a partir del texto libre de *_result.
     */
    public const POSITIVE_FLAG_FIELDS = ['cognition_positive', 'anxiety_positive', 'depression_positive', 'ptsd_positive'];

    protected function casts(): array
    {
        return [
            'contact_achieved' => 'boolean',
            'cognition_positive' => 'boolean',
            'anxiety_positive' => 'boolean',
            'depression_positive' => 'boolean',
            'ptsd_positive' => 'boolean',
            'readmission' => 'boolean',
            'return_to_work' => 'boolean',
            'referred_to' => 'array',
            'followed_up_at' => 'datetime',
        ];
    }

    public function case(): BelongsTo
    {
        return $this->belongsTo(PicsCase::class, 'pics_case_id');
    }

    public function responsible(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_user_id');
    }

    public function referrals(): HasMany
    {
        return $this->hasMany(PicsReferral::class);
    }

    public function checkpointLabel(): string
    {
        return self::CHECKPOINTS[$this->checkpoint] ?? $this->checkpoint;
    }
}

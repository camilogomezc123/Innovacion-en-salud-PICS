<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'pics_case_id', 'type', 'title', 'description', 'scheduled_at', 'responsible_user_id',
    'status', 'completed_at', 'completed_by', 'notes', 'created_by',
])]
class PicsAgendaItem extends Model
{
    public const TYPES = [
        'cita' => 'Cita',
        'terapia' => 'Terapia',
        'tarea' => 'Tarea',
        'recordatorio' => 'Recordatorio',
    ];

    public const STATUSES = [
        'pendiente' => 'Pendiente',
        'completada' => 'Completada',
        'cancelada' => 'Cancelada',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'completed_at' => 'datetime',
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

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }
}

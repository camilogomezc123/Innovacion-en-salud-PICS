<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'pics_case_id', 'caregiver_id', 'relationship', 'can_write_diary',
    'authorized_by', 'authorized_at', 'revoked_by', 'revoked_at',
])]
class CaregiverAuthorization extends Model
{
    protected function casts(): array
    {
        return [
            'can_write_diary' => 'boolean',
            'authorized_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function case(): BelongsTo
    {
        return $this->belongsTo(PicsCase::class, 'pics_case_id');
    }

    public function caregiver(): BelongsTo
    {
        return $this->belongsTo(Caregiver::class);
    }

    public function authorizedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'authorized_by');
    }

    public function revokedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revoked_by');
    }

    public function isActive(): bool
    {
        return $this->revoked_at === null;
    }
}

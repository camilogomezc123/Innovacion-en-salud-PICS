<?php

namespace App\Models;

use App\Concerns\PortalAccountAuthenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

#[Fillable(['identification', 'full_name', 'sex', 'age', 'email', 'password', 'must_change_password'])]
#[Hidden(['password', 'remember_token'])]
class Patient extends Model implements AuthenticatableContract, CanResetPasswordContract
{
    use PortalAccountAuthenticatable;

    public function portalGuardName(): string
    {
        return 'patient';
    }

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'must_change_password' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    public function cases(): HasMany
    {
        return $this->hasMany(AcvCase::class);
    }

    public function picsCases(): HasMany
    {
        return $this->hasMany(PicsCase::class);
    }

    public function goalProgressReports(): MorphMany
    {
        return $this->morphMany(GoalProgressReport::class, 'reporter');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['identification', 'full_name', 'sex', 'age'])]
class Patient extends Model
{
    public function cases(): HasMany
    {
        return $this->hasMany(AcvCase::class);
    }
}

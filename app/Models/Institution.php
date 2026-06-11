<?php

namespace App\Models;

use Database\Factories\InstitutionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

#[Fillable(['name', 'code', 'is_active'])]
class Institution extends Model
{
    /** @use HasFactory<InstitutionFactory> */
    use HasFactory;

    public function memberships(): MorphMany
    {
        return $this->morphMany(Membership::class, 'organization');
    }
}

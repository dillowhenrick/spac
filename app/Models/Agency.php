<?php

namespace App\Models;

use Database\Factories\AgencyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

#[Fillable(['name', 'code', 'is_active'])]
class Agency extends Model
{
    /** @use HasFactory<AgencyFactory> */
    use HasFactory;

    public function memberships(): MorphMany
    {
        return $this->morphMany(Membership::class, 'organization');
    }
}

<?php

namespace App\Models;

use App\Enums\Role;
use Database\Factories\MembershipFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable(['user_id', 'organization_type', 'organization_id', 'role', 'is_primary'])]
class Membership extends Model
{
    /** @use HasFactory<MembershipFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'role' => Role::class,
            'is_primary' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function organization(): MorphTo
    {
        return $this->morphTo();
    }
}

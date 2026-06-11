<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\Role;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(Membership::class);
    }

    public function agencyMemberships(): HasMany
    {
        return $this->hasMany(Membership::class)->where('organization_type', 'agency');
    }

    public function institutionMemberships(): HasMany
    {
        return $this->hasMany(Membership::class)->where('organization_type', 'institution');
    }

    public function hasRole(Role $role): bool
    {
        return $this->memberships()->where('role', $role->value)->exists();
    }

    public function isManagerOfInstitution(int $institutionId): bool
    {
        return $this->institutionMemberships()
            ->where('organization_id', $institutionId)
            ->where('role', Role::InstitutionManager->value)
            ->exists();
    }

    public function isStaffOfInstitution(int $institutionId): bool
    {
        return $this->institutionMemberships()
            ->where('organization_id', $institutionId)
            ->where('role', Role::InstitutionStaff->value)
            ->exists();
    }

    public function securityLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class, 'user_id')
            ->whereIn('event', ['login', 'logout', 'login_failed', 'two_factor_success', 'two_factor_failed'])
            ->latest();
    }
}

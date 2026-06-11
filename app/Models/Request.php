<?php

namespace App\Models;

use App\Enums\LegalProcess;
use App\Enums\NatureOfCase;
use App\Enums\RequestStatus;
use Carbon\CarbonImmutable;
use Database\Factories\RequestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'requester_id',
    'agency_id',
    'target_institution_id',
    'reference_number',
    'legal_process',
    'nature_of_case',
    'additional_context',
    'status',
    'submitted_at',
    'records_from',
    'records_to',
    'legal_process_signed_at',
    'warrant_expires_at',
    'executed_at',
    'request_due_at',
])]
class Request extends Model
{
    /** @use HasFactory<RequestFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => RequestStatus::class,
            'legal_process' => LegalProcess::class,
            'nature_of_case' => NatureOfCase::class,
            'submitted_at' => 'datetime',
            'records_from' => 'date',
            'records_to' => 'date',
            'legal_process_signed_at' => 'datetime',
            'warrant_expires_at' => 'datetime',
            'executed_at' => 'datetime',
            'request_due_at' => 'datetime',
        ];
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function targetInstitution(): BelongsTo
    {
        return $this->belongsTo(Institution::class, 'target_institution_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(RequestAttachment::class);
    }

    public function verification(): HasOne
    {
        return $this->hasOne(RequestVerification::class);
    }

    public function requestRoute(): HasOne
    {
        return $this->hasOne(RequestRoute::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(RequestAssignment::class);
    }

    public function response(): HasOne
    {
        return $this->hasOne(RequestResponse::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(RequestMessage::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class, 'auditable_id')
            ->where('auditable_type', 'request')
            ->latest();
    }

    public function isWarrantType(): bool
    {
        return $this->legal_process?->isWarrant() ?? false;
    }

    public function computeReturnDueAt(CarbonImmutable $executedAt): ?CarbonImmutable
    {
        if (! $this->isWarrantType()) {
            return null;
        }

        $baseline = $this->warrant_expires_at?->lt($executedAt)
            ? $this->warrant_expires_at
            : $executedAt;

        return $baseline->addHours(48);
    }

    public function isDraft(): bool
    {
        return $this->status === RequestStatus::Draft;
    }

    public function isSubmitted(): bool
    {
        return $this->status === RequestStatus::Submitted;
    }

    public function isVerified(): bool
    {
        return $this->status === RequestStatus::Verified;
    }

    public function isRouted(): bool
    {
        return $this->status === RequestStatus::Routed;
    }

    public function isAssigned(): bool
    {
        return $this->status === RequestStatus::Assigned;
    }

    public function isInProgress(): bool
    {
        return $this->status === RequestStatus::InProgress;
    }
}

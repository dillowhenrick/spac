<?php

namespace App\Models;

use App\Enums\ResponseStatus;
use Database\Factories\RequestResponseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['request_id', 'drafted_by', 'status', 'notes', 'submitted_at'])]
class RequestResponse extends Model
{
    /** @use HasFactory<RequestResponseFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => ResponseStatus::class,
            'submitted_at' => 'datetime',
        ];
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(Request::class);
    }

    public function drafter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'drafted_by');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(ResponseAttachment::class, 'response_id');
    }

    public function isDraft(): bool
    {
        return $this->status === ResponseStatus::Draft;
    }

    public function isPendingApproval(): bool
    {
        return $this->status === ResponseStatus::PendingApproval;
    }

    public function isApproved(): bool
    {
        return $this->status === ResponseStatus::Approved;
    }
}

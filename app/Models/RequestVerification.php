<?php

namespace App\Models;

use App\Enums\VerificationDecision;
use Database\Factories\RequestVerificationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['request_id', 'verified_by', 'decision', 'notes', 'verified_at'])]
class RequestVerification extends Model
{
    /** @use HasFactory<RequestVerificationFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'decision' => VerificationDecision::class,
            'verified_at' => 'datetime',
        ];
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(Request::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}

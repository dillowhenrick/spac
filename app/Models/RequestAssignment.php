<?php

namespace App\Models;

use Database\Factories\RequestAssignmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['request_id', 'assigned_by', 'assigned_to', 'is_self_assigned', 'assigned_at'])]
class RequestAssignment extends Model
{
    /** @use HasFactory<RequestAssignmentFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'is_self_assigned' => 'boolean',
            'assigned_at' => 'datetime',
        ];
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(Request::class);
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}

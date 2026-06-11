<?php

namespace App\Models;

use Database\Factories\RequestRouteFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['request_id', 'routed_by', 'institution_id', 'routed_at'])]
class RequestRoute extends Model
{
    /** @use HasFactory<RequestRouteFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'routed_at' => 'datetime',
        ];
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(Request::class);
    }

    public function router(): BelongsTo
    {
        return $this->belongsTo(User::class, 'routed_by');
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }
}

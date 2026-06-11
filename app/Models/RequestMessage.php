<?php

namespace App\Models;

use Database\Factories\RequestMessageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['request_id', 'sender_id', 'body'])]
class RequestMessage extends Model
{
    /** @use HasFactory<RequestMessageFactory> */
    use HasFactory;

    public function request(): BelongsTo
    {
        return $this->belongsTo(Request::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(MessageAttachment::class, 'message_id');
    }
}

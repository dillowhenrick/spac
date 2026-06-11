<?php

namespace App\Models;

use Database\Factories\MessageAttachmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['message_id', 'uploaded_by', 'original_name', 'path', 'mime_type', 'size'])]
class MessageAttachment extends Model
{
    /** @use HasFactory<MessageAttachmentFactory> */
    use HasFactory;

    public function message(): BelongsTo
    {
        return $this->belongsTo(RequestMessage::class, 'message_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}

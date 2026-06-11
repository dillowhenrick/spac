<?php

namespace App\Models;

use Database\Factories\RequestAttachmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['request_id', 'uploaded_by', 'original_name', 'path', 'mime_type', 'size'])]
class RequestAttachment extends Model
{
    /** @use HasFactory<RequestAttachmentFactory> */
    use HasFactory;

    public function request(): BelongsTo
    {
        return $this->belongsTo(Request::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}

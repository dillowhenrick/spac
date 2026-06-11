<?php

namespace App\Models;

use Database\Factories\ResponseAttachmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['response_id', 'uploaded_by', 'original_name', 'path', 'mime_type', 'size'])]
class ResponseAttachment extends Model
{
    /** @use HasFactory<ResponseAttachmentFactory> */
    use HasFactory;

    public function response(): BelongsTo
    {
        return $this->belongsTo(RequestResponse::class, 'response_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}

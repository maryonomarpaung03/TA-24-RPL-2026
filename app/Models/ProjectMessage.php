<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ProjectMessage extends Model
{
    protected $fillable = [
        'project_id',
        'user_id',
        'body',
        'attachment_path',
        'attachment_name',
        'attachment_mime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isImage(): bool
    {
        return $this->attachment_mime !== null
            && str_starts_with($this->attachment_mime, 'image/');
    }

    public function attachmentUrl(): ?string
    {
        return $this->attachment_path
            ? Storage::disk('public')->url($this->attachment_path)
            : null;
    }
}

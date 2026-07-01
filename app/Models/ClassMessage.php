<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClassMessage extends Model
{
    protected $fillable = [
        'academic_class_id',
        'user_id',
        'body',
        'attachment_path',
        'attachment_name',
        'attachment_mime',
    ];

    public function isImage(): bool
    {
        return $this->attachment_mime !== null
            && str_starts_with($this->attachment_mime, 'image/');
    }

    public function attachmentUrl(): ?string
    {
        return $this->attachment_path
            ? \Illuminate\Support\Facades\Storage::disk('public')->url($this->attachment_path)
            : null;
    }

    public function academicClass(): BelongsTo
    {
        return $this->belongsTo(AcademicClass::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

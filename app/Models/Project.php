<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    protected $table = 'projects';

    protected $fillable = [
        'name',
        'description',
        'status',
        'start_date',
        'end_date',
        'created_by',
    ];

    /**
     * Kode blade/controller lama memakai ->title; di DB kolomnya adalah name.
     */
    public function getTitleAttribute(): string
    {
        return (string) ($this->attributes['name'] ?? '');
    }

    /**
     * Kolom logo belum ada di skema standar migrasi; hindari error saat diakses.
     */
    public function getLogoAttribute(): ?string
    {
        if (! array_key_exists('logo', $this->attributes)) {
            return null;
        }

        return $this->attributes['logo'];
    }
}

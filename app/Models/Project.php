<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    /*
    table database
    */
    protected $table =
        'projects';

    protected $fillable = [
        'name',
        'description',
    /*
    created_at manual
    updated_at tidak ada
    */
    public $timestamps =
        false;

    /*
    mass assignment
    */
    protected $fillable = [

        'team_id',

        'created_by',

        'title',

        'description',

        'problem_definition',

        'logo',

        'status',

        'start_date',

        'end_date',
        'created_by',

        'created_at'
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

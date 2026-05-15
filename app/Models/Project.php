<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    protected $table = 'projects';

    public $timestamps = true;

    protected $fillable = [
        'team_id',
        'name',
        'description',
        'problem_definition',
        'logo',
        'status',
        'start_date',
        'end_date',
        'created_by',
        'lecturer_email',
        'lecturer_name',
        'group_name',
        'course_name',
        'planned_months',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
        ];
    }

    /**
     * Kode blade/controller lama memakai ->title; di DB kolomnya adalah name.
     */
    public function getTitleAttribute(): string
    {
        return (string) ($this->attributes['name'] ?? '');
    }

    public function getLogoAttribute(): ?string
    {
        if (! array_key_exists('logo', $this->attributes)) {
            return null;
        }

        return $this->attributes['logo'];
    }
}

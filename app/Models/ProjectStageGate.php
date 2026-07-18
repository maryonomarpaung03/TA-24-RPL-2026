<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectStageGate extends Model
{
    protected $fillable = ['project_id', 'stage', 'status', 'revision_count', 'submitted_by', 'submitted_at', 'reviewed_by', 'reviewed_at', 'approved_at', 'lecturer_note', 'summary'];

    protected function casts(): array
    {
        return ['submitted_at' => 'datetime', 'reviewed_at' => 'datetime', 'approved_at' => 'datetime', 'summary' => 'array'];
    }
}

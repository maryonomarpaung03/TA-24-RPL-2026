<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectStageCompletion extends Model
{
    protected $table = 'project_stage_completions';

    protected $fillable = [
        'project_id',
        'stage',
        'finalized_at',
        'finalized_by',
        'source',
        'reopen_count',
        'summary',
    ];

    protected function casts(): array
    {
        return [
            'finalized_at' => 'datetime',
            'summary' => 'array',
        ];
    }

    public function finalizer()
    {
        return $this->belongsTo(User::class, 'finalized_by');
    }
}

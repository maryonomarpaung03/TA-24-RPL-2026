<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StageReopenRequest extends Model
{
    protected $table = 'stage_reopen_requests';

    protected $fillable = [
        'project_id',
        'stage',
        'requested_by',
        'reason',
        'status',
        'lecturer_note',
        'reviewed_by',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
        ];
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}

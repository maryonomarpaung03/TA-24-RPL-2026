<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    protected $table = 'tasks';

    protected $fillable = [
        'project_id',
        'board_id',
        'milestone_id',
        'parent_task_id',
        'assigned_to',
        'task_title',
        'description',
        'priority',
        'status',
        'link',
        'submission_type',
        'attachment_path',
        'attachment_name',
        'attachment_mime',
        'progress_percent',
        'start_date',
        'due_date',
    ];
    public function comments()
    {
        return $this->hasMany(TaskComment::class, 'task_id');
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaskComment extends Model
{
    protected $table = 'task_comments';

    protected $fillable = [
        'task_id',
        'comment',
    ];

    public function comments()
    {
        return $this->hasMany(TaskComment::class, 'task_id');
    }
}
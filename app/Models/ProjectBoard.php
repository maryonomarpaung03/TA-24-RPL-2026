<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectBoard extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'name',
        'position',
    ];

    public function tasks()
    {
        return $this->hasMany(Task::class, 'board_id');
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    protected $table = 'projects';

    public $timestamps = false;

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
        'created_at'
    ];
}
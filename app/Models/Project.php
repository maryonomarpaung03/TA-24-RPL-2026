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

        'created_at'
    ];
}
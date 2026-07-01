<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClassRead extends Model
{
    protected $fillable = [
        'user_id',
        'academic_class_id',
        'last_read_at',
    ];

    protected function casts(): array
    {
        return [
            'last_read_at' => 'datetime',
        ];
    }
}

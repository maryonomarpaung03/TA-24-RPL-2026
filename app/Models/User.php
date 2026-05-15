<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable([
    'full_name',
    'username',
    'email',
    'password',
    'role',
    'nim',
    'nidn',
    'faculty_id',
    'study_program_id',
    'batch_year',
    'profile_photo',
    'created_at'
])]

#[Hidden([
    'password',
    'remember_token'
])]

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /*
    pakai tabel users custom
    */
    protected $table = 'users';

    /*
    primary key
    */
    protected $primaryKey = 'id';

    /*
    karena tidak ada updated_at
    */
    public $timestamps = false;

    /**
     * Casts
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    /**
     * Nama tampilan
     */
    public function displayName(): string
    {
        return (string) (
            $this->full_name
            ?? ''
        );
    }
}
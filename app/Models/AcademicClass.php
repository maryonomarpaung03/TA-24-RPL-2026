<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AcademicClass extends Model
{
    protected $fillable = [
        'lecturer_id',
        'fakultas',
        'jurusan',
        'name',
        'course_name',
        'academic_year',
        'semester',
        'description',
        'max_members',
        'join_code',
        'visibility',
        'co_lecturer_emails',
        'invited_student_emails',
    ];

    protected function casts(): array
    {
        return [
            'co_lecturer_emails' => 'array',
            'invited_student_emails' => 'array',
            'max_members' => 'integer',
        ];
    }

    public function lecturer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'lecturer_id');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'class_members', 'academic_class_id', 'user_id')
            ->withPivot('joined_at')
            ->withTimestamps();
    }

    public function memberCount(): int
    {
        return $this->members()->count();
    }

    public function isFull(): bool
    {
        if ($this->max_members === null) {
            return false;
        }

        return $this->memberCount() >= (int) $this->max_members;
    }

    public function canStudentJoin(User $user): bool
    {
        if ($this->visibility === 'closed') {
            $invited = collect($this->invited_student_emails ?? [])
                ->map(fn ($email) => strtolower(trim((string) $email)))
                ->filter();

            return $invited->contains(strtolower((string) $user->email));
        }

        return true;
    }

    public static function generateJoinCode(): string
    {
        do {
            $code = strtoupper(Str::random(6));
        } while (self::query()->where('join_code', $code)->exists());

        return $code;
    }

    public static function resolveJoinCode(?string $custom): string
    {
        $code = strtoupper(preg_replace('/\s+/', '', trim((string) $custom)));

        if ($code === '') {
            return self::generateJoinCode();
        }

        if (strlen($code) < 4 || strlen($code) > 12) {
            throw ValidationException::withMessages([
                'custom_join_code' => 'Kode kelas harus 4–12 karakter (huruf/angka).',
            ]);
        }

        if (! preg_match('/^[A-Z0-9]+$/', $code)) {
            throw ValidationException::withMessages([
                'custom_join_code' => 'Kode kelas hanya boleh huruf dan angka.',
            ]);
        }

        if (self::query()->where('join_code', $code)->exists()) {
            throw ValidationException::withMessages([
                'custom_join_code' => 'Kode kelas sudah digunakan.',
            ]);
        }

        return $code;
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\AcademicClass;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class LecturerClassController extends Controller
{
    public function store(Request $request)
    {
        if (Auth::user()->role !== 'lecturer') {
            abort(403);
        }

        $facultyPrograms = config('faculties.programs', []);

        $validated = $request->validate([
            'fakultas' => ['required', 'string', Rule::in(array_keys($facultyPrograms))],
            'jurusan' => [
                'required',
                'string',
                function (string $attribute, mixed $value, \Closure $fail) use ($request, $facultyPrograms): void {
                    $fakultas = $request->input('fakultas');
                    $programs = $facultyPrograms[$fakultas] ?? [];

                    if (! in_array($value, $programs, true)) {
                        $fail('Jurusan tidak valid untuk fakultas yang dipilih.');
                    }
                },
            ],
            'name' => ['required', 'string', 'max:255'],
            'course_name' => ['required', 'string', 'max:255'],
            'academic_year' => ['required', 'string', 'max:20'],
            'semester' => ['required', Rule::in(['Ganjil', 'Genap', 'Pendek'])],
            'description' => ['nullable', 'string', 'max:2000'],
            'max_members' => ['nullable', 'integer', 'min:1', 'max:500'],
            'custom_join_code' => ['nullable', 'string', 'max:12'],
            'visibility' => ['required', Rule::in(['public', 'closed'])],
            'invite_lecturer_emails' => ['nullable', 'array'],
            'invite_lecturer_emails.*' => [
                'email',
                Rule::exists('users', 'email')->where(fn ($q) => $q->where('role', 'lecturer')),
            ],
            'invite_student_emails' => ['nullable', 'array'],
            'invite_student_emails.*' => [
                'email',
                Rule::exists('users', 'email')->where(fn ($q) => $q->where('role', 'student')),
            ],
        ]);

        $inviteLecturerEmails = self::normalizeEmails($validated['invite_lecturer_emails'] ?? [], Auth::user()->email);
        $inviteStudentEmails = self::normalizeEmails($validated['invite_student_emails'] ?? []);

        $joinCode = AcademicClass::resolveJoinCode($validated['custom_join_code'] ?? null);

        $academicClass = AcademicClass::query()->create([
            'lecturer_id' => Auth::id(),
            'fakultas' => $validated['fakultas'],
            'jurusan' => $validated['jurusan'],
            'name' => $validated['name'],
            'course_name' => $validated['course_name'],
            'academic_year' => $validated['academic_year'],
            'semester' => $validated['semester'],
            'description' => $validated['description'] ?? null,
            'max_members' => $validated['max_members'] ?? null,
            'join_code' => $joinCode,
            'visibility' => $validated['visibility'],
            'co_lecturer_emails' => $inviteLecturerEmails ?: null,
            'invited_student_emails' => $inviteStudentEmails ?: null,
        ]);

        return back()->with(
            'class_created',
            [
                'name' => $academicClass->name,
                'jurusan' => $academicClass->jurusan,
                'course_name' => $academicClass->course_name,
                'academic_year' => $academicClass->academic_year,
                'semester' => $academicClass->semester,
                'join_code' => $academicClass->join_code,
                'visibility' => $academicClass->visibility,
            ]
        );
    }

    /**
     * @return array<int, array{email: string, name: string}>
     */
    public static function lecturersForInvite(): array
    {
        return self::usersForInvite('lecturer');
    }

    /**
     * @return array<int, array{email: string, name: string}>
     */
    public static function studentsForInvite(): array
    {
        return self::usersForInvite('student');
    }

    /**
     * @return array<int, array{email: string, name: string}>
     */
    private static function usersForInvite(string $role): array
    {
        if (! Auth::check()) {
            return [];
        }

        $query = User::query()->where('role', $role)->orderBy('full_name');

        if ($role === 'lecturer') {
            $query->where('id', '!=', Auth::id());
        }

        return $query
            ->get()
            ->map(fn (User $user) => [
                'email' => $user->email,
                'name' => $user->displayName(),
                'subtitle' => $user->nim ? 'NIM: '.$user->nim : null,
            ])
            ->all();
    }

    /**
     * @param  array<int, string>  $emails
     * @return array<int, string>
     */
    private static function normalizeEmails(array $emails, ?string $excludeEmail = null): array
    {
        $exclude = $excludeEmail ? strtolower(trim($excludeEmail)) : null;

        return collect($emails)
            ->map(fn ($email) => strtolower(trim((string) $email)))
            ->filter()
            ->unique()
            ->when($exclude, fn ($c) => $c->reject(fn ($email) => $email === $exclude))
            ->values()
            ->all();
    }
}

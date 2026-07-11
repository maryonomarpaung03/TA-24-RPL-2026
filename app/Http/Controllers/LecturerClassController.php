<?php

namespace App\Http\Controllers;

use App\Models\AcademicClass;
use App\Models\User;
use App\Support\ClassActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class LecturerClassController extends Controller
{
    /**
     * Daftar kelas yang dibuat dosen untuk dikelola.
     */
    public function index(Request $request)
    {
        if (Auth::user()->role !== 'lecturer') {
            abort(403);
        }

        $base = AcademicClass::query()->where('lecturer_id', Auth::id());

        $all = (clone $base)->get();

        $keyword = trim((string) $request->query('q', ''));
        $jurusan = (string) $request->query('jurusan', '');
        $semester = (string) $request->query('semester', '');
        $tahun = (string) $request->query('tahun', '');

        $query = (clone $base)->withCount('members');

        if ($keyword !== '') {
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'LIKE', '%'.$keyword.'%')
                    ->orWhere('course_name', 'LIKE', '%'.$keyword.'%')
                    ->orWhere('join_code', 'LIKE', '%'.$keyword.'%');
            });
        }

        if ($jurusan !== '') {
            $query->where('jurusan', $jurusan);
        }

        if ($semester !== '') {
            $query->where('semester', $semester);
        }

        if ($tahun !== '') {
            $query->where('academic_year', $tahun);
        }

        $classes = $query->latest()->get();

        // Snapshot jumlah baru untuk ditampilkan, lalu tandai semua terbaca.
        $unreadMap = ClassActivity::summary(Auth::user())['by_class'];
        ClassActivity::markAllRead(Auth::user());

        return view('DosenKelasSaya', [
            'classes' => $classes,
            'unreadMap' => $unreadMap,
            'totalClasses' => $all->count(),
            'filterState' => [
                'q' => $keyword,
                'jurusan' => $jurusan,
                'semester' => $semester,
                'tahun' => $tahun,
            ],
            'jurusanOptions' => $this->optionsFrom($all, 'jurusan'),
            'semesterOptions' => $this->optionsFrom($all, 'semester'),
            'tahunOptions' => $this->optionsFrom($all, 'academic_year'),
        ]);
    }

    /**
     * Nilai unik sebuah kolom untuk dijadikan opsi filter.
     *
     * @param  \Illuminate\Support\Collection<int, AcademicClass>  $classes
     * @return array<string, string>
     */
    private function optionsFrom($classes, string $column): array
    {
        return $classes
            ->pluck($column)
            ->filter()
            ->unique()
            ->sort()
            ->mapWithKeys(fn ($value) => [$value => $value])
            ->all();
    }

    /**
     * Perbarui informasi kelas milik dosen.
     */
    public function update(Request $request, $id)
    {
        $academicClass = $this->ownedClassOrFail($id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'course_name' => ['required', 'string', 'max:255'],
            'academic_year' => ['required', 'string', 'max:20'],
            'semester' => ['required', Rule::in(['Ganjil', 'Genap', 'Pendek'])],
            'description' => ['nullable', 'string', 'max:2000'],
            'max_members' => ['nullable', 'integer', 'min:1', 'max:500'],
            'visibility' => ['required', Rule::in(['public', 'closed'])],
        ]);

        $academicClass->update([
            'name' => $validated['name'],
            'course_name' => $validated['course_name'],
            'academic_year' => $validated['academic_year'],
            'semester' => $validated['semester'],
            'description' => $validated['description'] ?? null,
            'max_members' => $validated['max_members'] ?? null,
            'visibility' => $validated['visibility'],
        ]);

        return redirect()
            ->route('dosen.kelas')
            ->with('success', 'Kelas "'.$academicClass->name.'" berhasil diperbarui.');
    }

    /**
     * Hapus kelas milik dosen beserta anggota & chatnya.
     */
    public function destroy($id)
    {
        $academicClass = $this->ownedClassOrFail($id);
        $name = $academicClass->name;
        $academicClass->delete();

        return redirect()
            ->route('dosen.kelas')
            ->with('success', 'Kelas "'.$name.'" telah dihapus.');
    }

    private function ownedClassOrFail($id): AcademicClass
    {
        if (Auth::user()->role !== 'lecturer') {
            abort(403);
        }

        $academicClass = AcademicClass::query()->findOrFail($id);

        if ((int) $academicClass->lecturer_id !== (int) Auth::id()) {
            abort(403);
        }

        return $academicClass;
    }

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

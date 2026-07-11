<?php

namespace App\Http\Controllers;

use App\Models\AcademicClass;
use App\Models\ClassMember;
use App\Support\ClassActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StudentClassController extends Controller
{
    /**
     * Daftar kelas yang diikuti mahasiswa.
     */
    public function index(Request $request)
    {
        $all = ClassMember::query()
            ->with(['academicClass.lecturer'])
            ->where('user_id', (int) Auth::id())
            ->latest('joined_at')
            ->get()
            ->filter(fn (ClassMember $m) => $m->academicClass !== null);

        $keyword = trim((string) $request->query('q', ''));
        $matkul = (string) $request->query('matkul', '');
        $semester = (string) $request->query('semester', '');

        $classes = $all->filter(function (ClassMember $m) use ($keyword, $matkul, $semester) {
            $class = $m->academicClass;
            $lecturer = $class->lecturer?->full_name ?? '';

            if ($keyword !== '') {
                $haystack = mb_strtolower($class->name.' '.$class->course_name.' '.$lecturer.' '.$class->join_code);

                if (! str_contains($haystack, mb_strtolower($keyword))) {
                    return false;
                }
            }

            if ($matkul !== '' && $class->course_name !== $matkul) {
                return false;
            }

            if ($semester !== '' && $class->semester !== $semester) {
                return false;
            }

            return true;
        });

        // Snapshot jumlah baru untuk ditampilkan, lalu tandai semua terbaca.
        $unreadMap = ClassActivity::summary(Auth::user())['by_class'];
        ClassActivity::markAllRead(Auth::user());

        return view('KelasSaya', [
            'classes' => $classes,
            'unreadMap' => $unreadMap,
            'totalClasses' => $all->count(),
            'filterState' => [
                'q' => $keyword,
                'matkul' => $matkul,
                'semester' => $semester,
            ],
            'matkulOptions' => $all
                ->map(fn (ClassMember $m) => $m->academicClass->course_name)
                ->filter()->unique()->sort()
                ->mapWithKeys(fn ($v) => [$v => $v])
                ->all(),
            'semesterOptions' => $all
                ->map(fn (ClassMember $m) => $m->academicClass->semester)
                ->filter()->unique()->sort()
                ->mapWithKeys(fn ($v) => [$v => $v])
                ->all(),
        ]);
    }

    public function join(Request $request)
    {
        $validated = $request->validate([
            'join_code' => ['required', 'string', 'max:12'],
        ]);

        $code = strtoupper(preg_replace('/\s+/', '', $validated['join_code']));
        $academicClass = AcademicClass::query()->where('join_code', $code)->first();

        if (! $academicClass) {
            return back()
                ->withInput()
                ->with('open_join_class', true)
                ->with('error', 'Kode kelas tidak ditemukan. Periksa kembali kode dari dosen.');
        }

        $user = Auth::user();

        if (! $academicClass->canStudentJoin($user)) {
            return back()
                ->withInput()
                ->with('open_join_class', true)
                ->with('error', 'Kelas ini bersifat tertutup. Anda harus diundang dosen terlebih dahulu.');
        }

        if ($academicClass->isFull()) {
            return back()
                ->withInput()
                ->with('open_join_class', true)
                ->with('error', 'Kelas sudah penuh.');
        }

        $userId = (int) $user->id;

        $alreadyJoined = ClassMember::query()
            ->where('academic_class_id', $academicClass->id)
            ->where('user_id', $userId)
            ->exists();

        if ($alreadyJoined) {
            return redirect()
                ->route('classes.show', $academicClass->id)
                ->with('info', 'Anda sudah terdaftar di kelas '.$academicClass->name.'.');
        }

        ClassMember::query()->create([
            'academic_class_id' => $academicClass->id,
            'user_id' => $userId,
            'joined_at' => now(),
        ]);

        return redirect()
            ->route('classes.show', $academicClass->id)
            ->with(
                'success',
                sprintf('Berhasil bergabung ke kelas "%s" (%s).', $academicClass->name, $academicClass->course_name)
            );
    }
}

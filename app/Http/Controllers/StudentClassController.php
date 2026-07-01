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
    public function index()
    {
        $classes = ClassMember::query()
            ->with(['academicClass.lecturer'])
            ->where('user_id', (int) Auth::id())
            ->latest('joined_at')
            ->get()
            ->filter(fn (ClassMember $m) => $m->academicClass !== null);

        // Snapshot jumlah baru untuk ditampilkan, lalu tandai semua terbaca.
        $unreadMap = ClassActivity::summary(Auth::user())['by_class'];
        ClassActivity::markAllRead(Auth::user());

        return view('KelasSaya', [
            'classes' => $classes,
            'unreadMap' => $unreadMap,
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

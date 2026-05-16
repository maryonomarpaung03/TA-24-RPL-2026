<?php

namespace App\Http\Controllers;

use App\Models\AcademicClass;
use App\Models\ClassMember;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StudentClassController extends Controller
{
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
            return back()->with('info', 'Anda sudah terdaftar di kelas '.$academicClass->name.'.');
        }

        ClassMember::query()->create([
            'academic_class_id' => $academicClass->id,
            'user_id' => $userId,
            'joined_at' => now(),
        ]);

        return back()->with(
            'success',
            sprintf('Berhasil bergabung ke kelas "%s" (%s).', $academicClass->name, $academicClass->course_name)
        );
    }
}

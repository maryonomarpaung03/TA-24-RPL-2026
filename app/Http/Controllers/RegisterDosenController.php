<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisterDosenController extends Controller
{
    public function create()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.register-dosen');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'nidn' => ['required', 'string', 'max:32', Rule::unique('users', 'nidn')],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'confirmed', Password::min(6)],
            'birth_place_date' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:1000'],
            'phone' => ['required', 'string', 'max:32'],
            'gender' => ['required', Rule::in(['Laki-laki', 'Perempuan'])],
        ]);

        $username = strtolower(str_replace(' ', '', $validated['full_name']));

        if (User::where('username', $username)->exists()) {
            $username .= rand(100, 999);
        }

        User::create([
            'name' => $validated['full_name'],
            'full_name' => $validated['full_name'],
            'username' => $username,
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'lecturer',
            'nidn' => $validated['nidn'],
            'nim' => null,
            'birth_place_date' => $validated['birth_place_date'],
            'address' => $validated['address'],
            'phone' => $validated['phone'],
            'gender' => $validated['gender'],
            'jurusan' => null,
            'fakultas' => null,
            'faculty_id' => null,
            'study_program_id' => null,
            'batch_year' => null,
            'profile_photo' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()
            ->route('login')
            ->with('success', 'Akun dosen berhasil dibuat. Silakan login.');
    }
}

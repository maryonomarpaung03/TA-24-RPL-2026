<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Hash;

class RegisterController extends Controller
{
    /*
    =====================================
    HALAMAN REGISTER
    =====================================
    */
    public function create()
    {
        /*
        kalau sudah login
        redirect dashboard
        */
        if (Auth::check()) {
            return redirect()
                ->route('dashboard');
        }

        return view('auth.register');
    }

    /*
    =====================================
    PROSES REGISTER
    =====================================
    */
    public function store(
        Request $request
    )
    {
        $validated =
            $request->validate([

                'full_name' => [
                    'required',
                    'string',
                    'max:255'
                ],

                'email' => [
                    'required',
                    'email',
                    'max:255',
                    Rule::unique(
                        'users',
                        'email'
                    )
                ],

                'password' => [
                    'required',
                    'confirmed',
                    Password::min(6)
                ]
            ]);

        /*
        generate username
        dari nama lengkap
        */
        $username =
            strtolower(
                str_replace(
                    ' ',
                    '',
                    $validated[
                        'full_name'
                    ]
                )
            );

        /*
        kalau username sama
        tambah random angka
        */
        if (
            User::where(
                'username',
                $username
            )->exists()
        ) {

            $username .= rand(
                100,
                999
            );
        }

        /*
        simpan user
        ke tapjblct.users
        */
        $user =
            User::create([

                'full_name' =>
                    $validated[
                        'full_name'
                    ],

                'username' =>
                    $username,

                'email' =>
                    $validated[
                        'email'
                    ],

                'password' =>
                    Hash::make(
                        $validated[
                            'password'
                        ]
                    ),

                /*
                default role
                */
                'role' =>
                    'student',

                /*
                sementara null
                */
                'nim' => null,
                'nidn' => null,
                'faculty_id' => null,
                'study_program_id' => null,
                'batch_year' => null,
                'profile_photo' => null,

                'created_at' =>
                    now()
            ]);

        /*
        auto login
        setelah daftar
        */
        return redirect()
    ->route('login')
    ->with(
        'success',
        'Akun berhasil dibuat. Silakan login.'
    );
    }
}
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    /*
    ==========================
    HALAMAN LOGIN
    ==========================
    */
    public function create()
    {
        /*
        kalau sudah login
        langsung dashboard
        */
        if (Auth::check()) {
            return redirect()->route(
                'dashboard'
            );
        }

        return view('auth.login');
    }

    /*
    ==========================
    PROSES LOGIN
    ==========================
    */
  public function store(
    Request $request
)
{
    $credentials =
        $request->validate([
            'email' => [
                'required',
                'email'
            ],

            'password' => [
                'required'
            ]
        ]);

    if (!Auth::attempt(
        $credentials,
        $request->boolean(
            'remember'
        )
    )) {

        throw ValidationException::withMessages([
            'email' =>
                'Email atau kata sandi salah.'
        ]);
    }

    $request
        ->session()
        ->regenerate();

    $user = Auth::user();

    if ($user && $user->role === 'lecturer') {
        return redirect()->route('dosen.persetujuan');
    }

    return redirect()->route('dashboard');
}


    /*
    ==========================
    LOGOUT
    ==========================
    */
    public function destroy(
        Request $request
    )
    {
        Auth::logout();

        $request
            ->session()
            ->invalidate();

        $request
            ->session()
            ->regenerateToken();

        return redirect()
            ->route('login');
    }
}
